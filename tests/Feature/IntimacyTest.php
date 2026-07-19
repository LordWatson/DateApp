<?php

namespace Tests\Feature;

use App\Enums\IntimacyGameCategory;
use App\Enums\IntimacyGameIntensity;
use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\IntimacyGame;
use App\Models\Questionnaire;
use App\Models\User;
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

    public function test_intimacy_seeders_run_without_error(): void
    {
        $this->seed(IntimacyQuestionnaireSeeder::class);
        $this->seed(IntimacyGameSeeder::class);

        $this->assertGreaterThan(0, Questionnaire::where('is_intimacy', true)->count());
        $this->assertGreaterThan(0, IntimacyGame::count());
    }
}
