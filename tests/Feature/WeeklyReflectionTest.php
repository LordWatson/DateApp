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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
