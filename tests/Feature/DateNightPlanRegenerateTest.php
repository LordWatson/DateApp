<?php

namespace Tests\Feature;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateNightPlanRegenerateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    private DateNightPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.provider', 'null');

        $this->user = User::factory()->create(['display_name' => 'Alex']);
        $this->partner = User::factory()->create(['display_name' => 'Sam', 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        $questionnaire = Questionnaire::factory()->create(['is_solo' => false]);

        $this->plan = DateNightPlan::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $questionnaire->id])->id,
            'partner_two_response_id' => Response::factory()->completed()->create(['user_id' => $this->partner->id, 'questionnaire_id' => $questionnaire->id])->id,
            'summary' => 'The original summary.',
            'ai_enhanced' => true,
            'fallback_used' => false,
        ]);
    }

    public function test_a_steer_rewrites_the_plan_and_tells_the_partner(): void
    {
        $prompts = $this->recordPrompts(['summary' => 'A cheaper evening.']);

        $this->actingAs($this->user)
            ->post(route('date-night.regenerate', $this->plan), ['steer' => 'Make it cheaper'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $plan = $this->plan->fresh();
        $this->assertSame('A cheaper evening.', $plan->summary);
        $this->assertSame('Make it cheaper', $plan->last_steer);
        $this->assertSame(1, $plan->regeneration_count);
        $this->assertDatabaseCount('date_night_plans', 1);

        // The request and the plan it applies to both reach the AI.
        $this->assertStringContainsString('"requested_change":"Make it cheaper"', $prompts[0]);
        $this->assertStringContainsString('The original summary.', $prompts[0]);

        $notification = AppNotification::where('user_id', $this->partner->id)->sole();
        $this->assertSame('date_night_plan_ready', $notification->type->value);
        $this->assertStringContainsString('Make it cheaper', $notification->body);
    }

    public function test_the_plan_is_left_alone_when_the_ai_fails(): void
    {
        // No prompt template: the AI call throws.
        $this->actingAs($this->user)
            ->post(route('date-night.regenerate', $this->plan), ['steer' => 'Make it cheaper'])
            ->assertSessionHas('error');

        $plan = $this->plan->fresh();
        $this->assertSame('The original summary.', $plan->summary);
        $this->assertNull($plan->last_steer);
        $this->assertSame(0, $plan->regeneration_count);
        $this->assertDatabaseCount('app_notifications', 0);
    }

    public function test_a_plan_can_only_be_changed_three_times(): void
    {
        $this->recordPrompts(['summary' => 'Changed again.']);
        $this->plan->update(['regeneration_count' => DateNightPlan::MAX_REGENERATIONS]);

        $this->actingAs($this->user)
            ->post(route('date-night.regenerate', $this->plan), ['steer' => 'Make it cheaper'])
            ->assertSessionHas('error');

        $this->assertSame('The original summary.', $this->plan->fresh()->summary);
        $this->assertDatabaseCount('ai_generations', 0);
    }

    public function test_a_steer_is_required_and_limited_in_length(): void
    {
        $this->actingAs($this->user)->post(route('date-night.regenerate', $this->plan), ['steer' => ''])->assertSessionHasErrors('steer');
        $this->actingAs($this->user)->post(route('date-night.regenerate', $this->plan), ['steer' => 'ab'])->assertSessionHasErrors('steer');
        $this->actingAs($this->user)
            ->post(route('date-night.regenerate', $this->plan), ['steer' => str_repeat('a', 201)])
            ->assertSessionHasErrors('steer');
    }

    public function test_a_stranger_cannot_change_a_plan(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('date-night.regenerate', $this->plan), ['steer' => 'Make it cheaper'])
            ->assertForbidden();
    }

    public function test_changing_a_plan_is_rate_limited(): void
    {
        $this->assertContains('throttle:ai', app('router')->getRoutes()->getByName('date-night.regenerate')->gatherMiddleware());
    }

    public function test_the_plan_page_says_whether_it_can_still_be_changed(): void
    {
        $this->actingAs($this->user)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page
                ->where('plan.can_regenerate', true)
                ->where('plan.regenerations_left', DateNightPlan::MAX_REGENERATIONS)
            );

        $this->plan->update(['regeneration_count' => DateNightPlan::MAX_REGENERATIONS]);

        $this->actingAs($this->user)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page->where('plan.can_regenerate', false)->where('plan.regenerations_left', 0));
    }

    public function test_the_plan_cannot_be_changed_until_both_partners_allow_ai(): void
    {
        $this->partner->recordAiConsent(false);

        $this->actingAs($this->user)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page->where('plan.can_regenerate', false)->where('plan.ai_available', false));
    }

    /**
     * Swap the AI for one that answers with the given fields and keeps the prompts it was sent.
     *
     * @param  array<string, string>  $answer
     * @return \ArrayObject<int, string>
     */
    private function recordPrompts(array $answer): \ArrayObject
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create(['user_prompt_template' => '{{context}}']);

        $prompts = new \ArrayObject;

        $this->app->instance(AIProvider::class, new class($answer, $prompts) implements AIProvider
        {
            /**
             * @param  array<string, string>  $answer
             * @param  \ArrayObject<int, string>  $prompts
             */
            public function __construct(private array $answer, private \ArrayObject $prompts) {}

            public function name(): string
            {
                return 'spy';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->prompts->append($request->userPrompt);

                return AIResponse::success($request->useCase, $this->answer, 'spy-model', []);
            }
        });

        return $prompts;
    }
}
