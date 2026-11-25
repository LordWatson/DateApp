<?php

namespace Tests\Feature;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\CalendarEvent;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\User;
use App\Models\WeeklyReflection;
use App\Services\WeeklyReflectionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WeeklyReflectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    /** @var array<int, string> */
    private array $prompts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Alex Watson', 'display_name' => null]);
        $this->partner = User::factory()->create(['name' => 'Rimma Stone', 'display_name' => null, 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::WeeklyReflection)->create([
            'user_prompt_template' => 'LOG: {{context}}',
        ]);

        $this->app->instance(AIProvider::class, new class($this->prompts) implements AIProvider
        {
            /** @param array<int, string> $prompts */
            public function __construct(private array &$prompts) {}

            public function name(): string
            {
                return 'fake';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->prompts[] = $request->userPrompt;

                return AIResponse::success($request->useCase, [
                    'headline' => 'Reflection '.count($this->prompts),
                    'summary' => 'Summary.',
                    'highlights' => ['One.'],
                    'gentle_suggestion' => 'Suggestion.',
                    'encouragement' => 'Encouragement.',
                ], 'fake-model');
            }
        });
    }

    public function test_the_weeks_activity_from_both_partners_reaches_the_prompt(): void
    {
        Moment::factory()->create(['user_id' => $this->partner->id, 'title' => 'Sunset at the harbour', 'date' => now()->subDays(2)]);
        Moment::factory()->create(['user_id' => $this->user->id, 'title' => 'Ancient history', 'date' => now()->subDays(20), 'private_notes' => 'keep this secret']);
        Moment::factory()->create(['user_id' => $this->user->id, 'title' => 'Pizza night', 'date' => now(), 'private_notes' => 'keep this secret']);
        LoveNote::factory()->create(['sender_id' => $this->partner->id, 'recipient_id' => $this->user->id, 'message' => 'Thinking of you']);
        CalendarEvent::factory()->create(['user_id' => $this->user->id, 'title' => 'Jazz club', 'date' => now()->addDays(3)]);

        $this->actingAs($this->user)->get(route('weekly-reflection.index'))->assertOk();

        $this->assertCount(1, $this->prompts);
        $prompt = $this->prompts[0];

        foreach (['Sunset at the harbour', 'Pizza night', 'Thinking of you', 'Jazz club', 'Alex', 'Rimma'] as $expected) {
            $this->assertStringContainsString($expected, $prompt);
        }

        $this->assertStringNotContainsString('{{context}}', $prompt);
        $this->assertStringNotContainsString('Ancient history', $prompt);
        $this->assertStringNotContainsString('keep this secret', $prompt);
        $this->assertStringNotContainsString('Watson', $prompt);
    }

    public function test_both_partners_see_the_same_reflection(): void
    {
        $this->actingAs($this->user)->get(route('weekly-reflection.index'))
            ->assertInertia(fn ($page) => $page->where('reflection.headline', 'Reflection 1'));

        $this->actingAs($this->partner)->get(route('weekly-reflection.index'))
            ->assertInertia(fn ($page) => $page->where('reflection.headline', 'Reflection 1')->has('history', 0));

        $this->assertCount(1, $this->prompts);
        $this->assertSame(1, WeeklyReflection::count());
    }

    public function test_regenerating_replaces_the_shared_reflection_for_both_partners(): void
    {
        $this->actingAs($this->user)->get(route('weekly-reflection.index'));

        $this->actingAs($this->partner)->post(route('weekly-reflection.regenerate'))
            ->assertRedirect(route('weekly-reflection.index'));

        $this->assertSame(1, WeeklyReflection::count());

        $this->actingAs($this->user)->get(route('weekly-reflection.index'))
            ->assertInertia(fn ($page) => $page->where('reflection.headline', 'Reflection 2'));
    }

    public function test_fallback_still_names_what_happened_when_the_ai_is_unavailable(): void
    {
        AiPromptTemplate::query()->delete();
        Moment::factory()->create(['user_id' => $this->user->id, 'title' => 'Pizza night', 'date' => now()]);

        $this->actingAs($this->user)->get(route('weekly-reflection.index'))->assertOk();

        $reflection = WeeklyReflection::sole();

        $this->assertTrue($reflection->fallback_used);
        $this->assertStringContainsString('Pizza night', implode(' ', $reflection->highlights));
    }

    public function test_a_user_without_a_partner_gets_solo_wording_in_the_plain_recap(): void
    {
        AiPromptTemplate::query()->delete();
        $solo = User::factory()->create();
        Moment::factory()->create(['user_id' => $solo->id, 'title' => 'Pizza night', 'date' => now()]);

        $this->actingAs($solo)->get(route('weekly-reflection.index'))->assertOk();

        $reflection = WeeklyReflection::sole();
        $text = implode(' ', [$reflection->headline, $reflection->summary, $reflection->gentle_suggestion, $reflection->encouragement, ...$reflection->highlights]);

        $this->assertSame('Your Week', $reflection->headline);
        $this->assertStringContainsString('Pizza night', $text);
        $this->assertStringNotContainsStringIgnoringCase('together', $text);
        $this->assertStringNotContainsStringIgnoringCase('each other', $text);
    }

    public function test_regenerating_is_throttled(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($this->user)->post(route('weekly-reflection.regenerate'))->assertRedirect();
        }

        $this->actingAs($this->user)->post(route('weekly-reflection.regenerate'))->assertTooManyRequests();

        $this->assertCount(20, $this->prompts);
    }

    public function test_generation_holds_one_lock_per_couple_so_both_partners_cannot_each_create_a_row(): void
    {
        $service = app(WeeklyReflectionService::class);
        $key = $service->lockKey($this->user, CarbonImmutable::now());

        $this->assertSame($key, $service->lockKey($this->partner, CarbonImmutable::now()));

        // The AI call happens in the middle of generation: the partner's request could not get the lock then.
        $freeDuringGeneration = null;
        $this->app->instance(AIProvider::class, new class($freeDuringGeneration, $key) implements AIProvider
        {
            public function __construct(private ?bool &$free, private string $key) {}

            public function name(): string
            {
                return 'fake';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->free = Cache::lock($this->key, 5)->get();

                return AIResponse::failure($request->useCase, 'skipped', 'not needed');
            }
        });

        $this->actingAs($this->user)->get(route('weekly-reflection.index'))->assertOk();

        $this->assertFalse($freeDuringGeneration);
        $this->assertTrue(Cache::lock($key, 5)->get(), 'The lock is released once the reflection is saved.');
        $this->assertSame(1, WeeklyReflection::count());
    }
}
