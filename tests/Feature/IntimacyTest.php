<?php

namespace Tests\Feature;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\IntimacyGameCategory;
use App\Enums\IntimacyGameIntensity;
use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\IntimacyGame;
use App\Models\Questionnaire;
use App\Models\User;
use Database\Seeders\AiPromptTemplateSeeder;
use Database\Seeders\IntimacyGameSeeder;
use Database\Seeders\IntimacyQuestionnaireSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntimacyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_intimacy_index_lists_intimacy_questionnaires_and_games(): void
    {
        Questionnaire::create([
            'title' => 'Test Intimacy Q',
            'slug' => 'test-intimacy-q',
            'description' => 'A test intimacy questionnaire.',
            'emoji' => '🔥',
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
            'estimated_minutes' => 5,
            'display_order' => 1,
            'is_intimacy' => true,
        ]);

        Questionnaire::create([
            'title' => 'Non Intimacy',
            'slug' => 'non-intimacy',
            'description' => 'Should not appear.',
            'emoji' => '🌱',
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
            'display_order' => 2,
            'is_intimacy' => false,
        ]);

        IntimacyGame::create([
            'title' => 'Test Game',
            'slug' => 'test-game',
            'emoji' => '🎲',
            'tagline' => 'Cheeky test',
            'description' => 'desc',
            'how_to_play' => 'play',
            'players' => 2,
            'estimated_minutes' => 10,
            'intensity' => IntimacyGameIntensity::Flirty,
            'category' => IntimacyGameCategory::Game,
            'prompts' => ['A', 'B'],
            'display_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('intimacy.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('intimacy/Index')
            ->has('questionnaires', 1)
            ->where('questionnaires.0.slug', 'test-intimacy-q')
            ->has('games', 1)
            ->where('games.0.slug', 'test-game')
            ->where('games.0.intensity', 'flirty')
        );
    }

    public function test_intimacy_game_show_returns_game(): void
    {
        $game = IntimacyGame::create([
            'title' => 'Truth or Dare',
            'slug' => 'truth-or-dare',
            'emoji' => '🎲',
            'tagline' => 'Fun',
            'description' => 'desc',
            'how_to_play' => 'play',
            'players' => 2,
            'estimated_minutes' => 20,
            'intensity' => IntimacyGameIntensity::Spicy,
            'category' => IntimacyGameCategory::Dare,
            'prompts' => ['prompt one', 'prompt two'],
            'display_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('intimacy.games.show', $game));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('intimacy/Game')
            ->where('game.slug', 'truth-or-dare')
            ->where('game.intensity', 'spicy')
            ->has('game.prompts', 2)
        );
    }

    public function test_inactive_game_returns_404(): void
    {
        $game = IntimacyGame::create([
            'title' => 'Hidden',
            'slug' => 'hidden-game',
            'description' => 'desc',
            'how_to_play' => 'play',
            'players' => 2,
            'intensity' => IntimacyGameIntensity::Flirty,
            'category' => IntimacyGameCategory::Game,
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->get(route('intimacy.games.show', $game))
            ->assertNotFound();
    }

    /**
     * A game with stored cards, and an AI provider that answers with `$data` (or fails when null).
     *
     * @param  array<string, mixed>|null  $data
     * @return array{IntimacyGame, object{requests: list<AIRequest>}}
     */
    private function gameWithAi(?array $data): array
    {
        $this->seed(AiPromptTemplateSeeder::class);

        $game = IntimacyGame::create([
            'title' => 'Truth or Dare',
            'slug' => 'truth-or-dare',
            'description' => 'A grown-up spin on the classic.',
            'how_to_play' => 'Take turns choosing truth or dare.',
            'players' => 2,
            'intensity' => IntimacyGameIntensity::Spicy,
            'category' => IntimacyGameCategory::Dare,
            'prompts' => ['Dare: Stored card.'],
            'is_active' => true,
        ]);

        $provider = new class($data) implements AIProvider
        {
            /** @var list<AIRequest> */
            public array $requests = [];

            /** @param array<string, mixed>|null $data */
            public function __construct(private ?array $data) {}

            public function name(): string
            {
                return 'spy';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->requests[] = $request;

                return $this->data === null
                    ? AIResponse::failure($request->useCase, 'http_error', 'down', 'spy-model')
                    : AIResponse::success($request->useCase, $this->data, 'spy-model');
            }
        };

        $this->app->instance(AIProvider::class, $provider);

        return [$game, $provider];
    }

    public function test_cards_are_written_by_ai_in_one_request_from_the_games_own_context(): void
    {
        [$game, $provider] = $this->gameWithAi(['cards' => [
            'Dare: New card.',
            '  Truth: Another new card.  ',
            'dare: new card.',
            'Dare: Stored card.',
            'Dare: Already played.',
            '',
            42,
        ]]);

        $this->actingAs($this->user)
            ->postJson(route('intimacy.games.cards', $game), ['seen' => ['Dare: Already played.']])
            ->assertOk()
            // Trimmed; duplicates, stored cards, played cards and non-text are dropped.
            ->assertExactJson(['cards' => ['Dare: New card.', 'Truth: Another new card.']]);

        $this->assertCount(1, $provider->requests);
        $prompt = $provider->requests[0]->userPrompt;
        $this->assertStringContainsString('"count":10', $prompt);
        $this->assertStringContainsString('Take turns choosing truth or dare.', $prompt);
        $this->assertStringContainsString('"intensity":"'.IntimacyGameIntensity::Spicy->label().'"', $prompt);
        $this->assertStringContainsString('Dare: Stored card.', $prompt);
        $this->assertStringContainsString('Dare: Already played.', $prompt);
    }

    public function test_cards_are_empty_when_ai_fails_so_the_page_plays_the_stored_cards(): void
    {
        [$game] = $this->gameWithAi(null);

        $this->actingAs($this->user)
            ->postJson(route('intimacy.games.cards', $game))
            ->assertOk()
            ->assertExactJson(['cards' => []]);
    }

    public function test_cards_cannot_be_requested_for_an_inactive_game(): void
    {
        [$game, $provider] = $this->gameWithAi(['cards' => ['Dare: New card.']]);
        $game->update(['is_active' => false]);

        $this->actingAs($this->user)->postJson(route('intimacy.games.cards', $game))->assertNotFound();
        $this->assertCount(0, $provider->requests);
    }

    public function test_intimacy_seeders_run_without_error(): void
    {
        $this->seed(IntimacyQuestionnaireSeeder::class);
        $this->seed(IntimacyGameSeeder::class);

        $this->assertGreaterThan(0, Questionnaire::where('is_intimacy', true)->count());
        $this->assertGreaterThan(0, IntimacyGame::count());
    }
}
