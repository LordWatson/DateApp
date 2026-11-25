<?php

namespace Tests\Feature;

use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\Answer;
use App\Models\DateNightPlan;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plan-scoped comparison pages, history filter and pagination, the print page and "Try again with AI".
 */
class DateNightPlanFinishesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    private Questionnaire $questionnaire;

    private Response $mine;

    private Response $theirs;

    private DateNightPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.provider', 'null');

        $this->user = User::factory()->create(['display_name' => 'Alex']);
        $this->partner = User::factory()->create(['display_name' => 'Sam', 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        $this->questionnaire = Questionnaire::factory()->create(['is_solo' => false]);
        $question = Question::factory()->singleChoice()->create(['questionnaire_id' => $this->questionnaire->id, 'display_order' => 1]);
        $option = QuestionOption::factory()->create(['question_id' => $question->id, 'value' => 'pizza', 'title' => 'Pizza']);

        $this->mine = Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $this->questionnaire->id]);
        $this->theirs = Response::factory()->completed()->create(['user_id' => $this->partner->id, 'questionnaire_id' => $this->questionnaire->id]);

        foreach ([$this->mine, $this->theirs] as $response) {
            Answer::factory()->create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'question_option_id' => $option->id,
                'value' => 'pizza',
            ]);
        }

        $this->plan = $this->planFor($this->mine, $this->theirs, ['theme' => 'Game Night', 'fallback_used' => true]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function planFor(Response $one, Response $two, array $attributes = []): DateNightPlan
    {
        return DateNightPlan::factory()->create([
            'questionnaire_id' => $one->questionnaire_id,
            'partner_one_response_id' => $one->id,
            'partner_two_response_id' => $two->id,
            'compatibility_score' => 80,
            ...$attributes,
        ]);
    }

    private function soloPlan(array $attributes = []): DateNightPlan
    {
        return $this->planFor($this->mine, $this->mine, ['is_solo' => true, 'compatibility_score' => 100, ...$attributes]);
    }

    // ─── Comparison pages (2.7) ──────────────────────────────────────────────

    public function test_participants_see_the_answers_page_from_their_own_side(): void
    {
        // The plan lists the partner first; each viewer still sees their own answers as "mine".
        $plan = $this->planFor($this->theirs, $this->mine);

        $this->actingAs($this->user)
            ->get(route('date-night.answers', $plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/PartnerAnswers')
                ->where('partner_name', 'Sam')
                ->where('plan_id', $plan->id)
                ->where('grouped_answers.0.my_answers.0.option_title', 'Pizza')
                ->where('grouped_answers.0.partner_answers.0.option_title', 'Pizza'));

        $this->actingAs($this->partner)
            ->get(route('date-night.answers', $plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('partner_name', 'Alex'));
    }

    public function test_participants_see_the_compatibility_page_for_the_plans_responses(): void
    {
        // A newer in-progress attempt must not be compared instead of the plan's responses.
        Response::factory()->create(['user_id' => $this->user->id, 'questionnaire_id' => $this->questionnaire->id]);

        $this->actingAs($this->user)
            ->get(route('date-night.compatibility', $this->plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Compatibility')
                ->where('partner_completed', true)
                ->where('plan_id', $this->plan->id)
                ->where('compatibility.percentage', 100)
                ->has('compatibility.matched', 1));
    }

    public function test_comparison_pages_still_work_after_the_couple_disconnects(): void
    {
        $this->user->update(['partner_id' => null]);
        $this->partner->update(['partner_id' => null]);

        $this->actingAs($this->user)
            ->get(route('date-night.compatibility', $this->plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('compatibility.percentage', 100));
    }

    public function test_a_stranger_cannot_open_the_comparison_pages(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('date-night.answers', $this->plan))->assertForbidden();
        $this->actingAs($stranger)->get(route('date-night.compatibility', $this->plan))->assertForbidden();
    }

    public function test_solo_plans_have_no_comparison_pages(): void
    {
        $plan = $this->soloPlan();

        $this->actingAs($this->user)->get(route('date-night.answers', $plan))->assertNotFound();
        $this->actingAs($this->user)->get(route('date-night.compatibility', $plan))->assertNotFound();
    }

    public function test_questionnaire_scoped_routes_redirect_to_the_plan(): void
    {
        $this->actingAs($this->user)
            ->get(route('questionnaires.partner-answers', $this->questionnaire->slug))
            ->assertRedirect(route('date-night.answers', $this->plan));

        $this->actingAs($this->user)
            ->get(route('questionnaires.compatibility', $this->questionnaire->slug))
            ->assertRedirect(route('date-night.compatibility', $this->plan));
    }

    // ─── History (2.16) ──────────────────────────────────────────────────────

    public function test_history_filters_by_theme_and_lists_the_users_themes(): void
    {
        $this->planFor($this->mine, $this->theirs, ['theme' => 'Movie Marathon']);

        $this->actingAs($this->user)
            ->get(route('date-night.history', ['theme' => 'Movie Marathon']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('plans.data', 1)
                ->where('plans.data.0.theme', 'Movie Marathon')
                ->where('themes', ['Game Night', 'Movie Marathon'])
                ->where('filters.theme', 'Movie Marathon'));
    }

    public function test_history_is_paginated_and_keeps_the_filters_in_the_links(): void
    {
        foreach (range(1, 11) as $i) {
            $this->planFor($this->mine, $this->theirs, ['theme' => 'Game Night']);
        }

        $this->actingAs($this->user)
            ->get(route('date-night.history', ['theme' => 'Game Night']))
            ->assertInertia(fn ($page) => $page
                ->has('plans.data', 10)
                ->where('plans.total', 12)
                ->where('plans.last_page', 2)
                ->where('plans.links.2.url', fn (string $url) => str_contains($url, 'theme=Game') && str_contains($url, 'page=2')));

        $this->actingAs($this->user)
            ->get(route('date-night.history', ['theme' => 'Game Night', 'page' => 2]))
            ->assertInertia(fn ($page) => $page->has('plans.data', 2));
    }

    public function test_history_only_lists_the_users_own_plans(): void
    {
        DateNightPlan::factory()->create(['theme' => 'Someone Else']);

        $this->actingAs($this->user)
            ->get(route('date-night.history'))
            ->assertInertia(fn ($page) => $page->has('plans.data', 1)->where('themes', ['Game Night']));
    }

    // ─── Print page (2.16) ───────────────────────────────────────────────────

    public function test_print_page_shows_location_and_local_suggestions(): void
    {
        $this->plan->update([
            'location_label' => 'Bath, England',
            'local_suggestions' => [['name' => 'The Scallop Shell', 'category' => 'Restaurant', 'description' => 'Fish and chips by the river.']],
        ]);

        $this->actingAs($this->user)
            ->get(route('date-night.export', $this->plan))
            ->assertOk()
            ->assertSee('near Bath, England')
            ->assertSee('The Scallop Shell')
            ->assertSee('Fish and chips by the river.')
            ->assertSee('80%')
            ->assertSee('Sam')
            ->assertSee('Print');
    }

    public function test_print_page_for_a_solo_plan_has_no_score_and_one_name(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('date-night.export', $this->soloPlan()))
            ->assertOk()
            ->assertSee('Alex')
            ->assertDontSee('Compatibility')
            ->assertDontSee('100%');

        $this->assertSame(1, substr_count($response->getContent(), '<div class="partner-chip">'));
    }

    // ─── Try again with AI (2.16) ────────────────────────────────────────────

    public function test_plan_page_exposes_the_ai_flags(): void
    {
        $this->actingAs($this->user)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page->where('plan.fallback_used', true)->where('plan.ai_enhanced', false));
    }

    public function test_retry_rewrites_a_fallback_plan_in_place(): void
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $this->actingAs($this->user)
            ->from(route('date-night.show', $this->plan))
            ->post(route('date-night.retry-ai', $this->plan))
            ->assertRedirect(route('date-night.show', $this->plan))
            ->assertSessionHas('success');

        $plan = $this->plan->fresh();
        $this->assertFalse($plan->fallback_used);
        $this->assertTrue($plan->ai_enhanced);
        $this->assertSame('A calm, connection-focused evening at home.', $plan->summary);
        $this->assertSame($this->plan->activity, $plan->activity);
        $this->assertDatabaseCount('date_night_plans', 1);
    }

    public function test_retry_leaves_the_plan_alone_when_ai_fails_again(): void
    {
        // No prompt template: the AI call throws.
        $this->actingAs($this->user)
            ->post(route('date-night.retry-ai', $this->plan))
            ->assertRedirect()
            ->assertSessionHas('error');

        $plan = $this->plan->fresh();
        $this->assertTrue($plan->fallback_used);
        $this->assertSame($this->plan->summary, $plan->summary);
    }

    public function test_retry_is_refused_for_a_plan_that_did_not_fall_back(): void
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();
        $this->plan->update(['fallback_used' => false]);

        $this->actingAs($this->user)
            ->post(route('date-night.retry-ai', $this->plan))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ai_generations', 0);
    }

    public function test_a_stranger_cannot_retry_a_plan(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('date-night.retry-ai', $this->plan))
            ->assertForbidden();
    }

    public function test_retry_is_rate_limited(): void
    {
        $this->assertContains('throttle:ai', app('router')->getRoutes()->getByName('date-night.retry-ai')->gatherMiddleware());
    }
}
