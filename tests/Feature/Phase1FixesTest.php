<?php

namespace Tests\Feature;

use App\Enums\CompletionStatus;
use App\Enums\NotificationType;
use App\Jobs\GenerateDateNightPlanJob;
use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use App\Models\User;
use App\Services\GlobalSearchService;
use App\Services\QuestionnaireService;
use App\Services\TimelineBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Regression tests for the "broken things users hit" phase of docs/ROADMAP.md.
 */
class Phase1FixesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.provider', 'null');

        $this->user = User::factory()->create();
        $this->partner = User::factory()->create(['partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);
    }

    private function questionnaire(array $attributes = []): Questionnaire
    {
        $questionnaire = Questionnaire::factory()->create($attributes);
        // Optional free text: these tests are not about answer validation.
        Question::factory()->text()->create(['questionnaire_id' => $questionnaire->id, 'display_order' => 1, 'required' => false]);

        return $questionnaire;
    }

    public function test_opening_a_question_after_finishing_does_not_start_a_new_attempt(): void
    {
        $questionnaire = $this->questionnaire(['is_solo' => true]);
        Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $questionnaire->id]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.question', [$questionnaire->slug, 1]))
            ->assertRedirect(route('questionnaires.show', $questionnaire->slug));

        $this->actingAs($this->user)
            ->postJson(route('questionnaires.answer', [$questionnaire->slug, 1]), ['value' => 'x'])
            ->assertStatus(409);

        $this->assertSame(1, Response::where('user_id', $this->user->id)->count());
        $this->assertSame(0, Response::where('status', CompletionStatus::InProgress)->count());
    }

    public function test_a_non_numeric_question_number_is_not_found(): void
    {
        $questionnaire = $this->questionnaire();

        $this->actingAs($this->user)->get("/questionnaires/{$questionnaire->slug}/question/abc")->assertNotFound();
    }

    public function test_the_completion_screen_goes_to_the_plan_once_it_exists(): void
    {
        $questionnaire = $this->questionnaire(['is_solo' => true]);

        $this->actingAs($this->user)->post(route('questionnaires.start', $questionnaire->slug));
        $this->actingAs($this->user)->post(route('questionnaires.finish', $questionnaire->slug));

        $plan = DateNightPlan::sole();

        $this->actingAs($this->user)
            ->get(route('questionnaires.complete', $questionnaire->slug))
            ->assertRedirect(route('date-night.show', $plan));
    }

    public function test_the_completion_screen_says_when_there_is_no_partner_to_wait_for(): void
    {
        $single = User::factory()->create();
        $questionnaire = $this->questionnaire(['is_solo' => false]);

        $this->actingAs($single)->post(route('questionnaires.start', $questionnaire->slug));
        $this->actingAs($single)->post(route('questionnaires.finish', $questionnaire->slug));

        $this->actingAs($single)
            ->get(route('questionnaires.complete', $questionnaire->slug))
            ->assertInertia(fn ($page) => $page->where('has_partner', false)->where('awaiting_plan', false));
    }

    public function test_a_failed_plan_job_tells_everyone_who_was_waiting(): void
    {
        $questionnaire = $this->questionnaire();

        (new GenerateDateNightPlanJob($this->user, $this->partner, $questionnaire))->failed(new RuntimeException('boom'));

        foreach ([$this->user, $this->partner] as $person) {
            $this->assertSame(1, AppNotification::where('user_id', $person->id)->where('type', NotificationType::PlanFailed)->count());
        }
    }

    public function test_a_saved_profile_only_fills_in_its_own_questionnaire(): void
    {
        $mine = $this->questionnaire();
        $other = $this->questionnaire();
        $otherQuestion = $other->questions()->first();

        $profile = SavedProfile::factory()->create(['user_id' => $this->user->id]);
        SavedProfileAnswer::factory()->create([
            'saved_profile_id' => $profile->id,
            'question_id' => $otherQuestion->id,
            'value' => 'from another questionnaire',
        ]);

        $response = Response::factory()->create(['user_id' => $this->user->id, 'questionnaire_id' => $mine->id]);
        app(QuestionnaireService::class)->applyProfile($response, $profile);

        $this->assertSame(0, $response->answers()->count());

        // The picker on the intro page only offers profiles that fit.
        $this->actingAs($this->user)->get(route('questionnaires.show', $mine->slug))
            ->assertInertia(fn ($page) => $page->has('saved_profiles', 0));
        $this->actingAs($this->user)->get(route('questionnaires.show', $other->slug))
            ->assertInertia(fn ($page) => $page->has('saved_profiles', 1));
    }

    public function test_solo_plans_carry_no_compatibility_score_and_are_not_past_couple_results(): void
    {
        $questionnaire = $this->questionnaire(['is_solo' => true]);

        $this->actingAs($this->user)->post(route('questionnaires.start', $questionnaire->slug));
        $this->actingAs($this->user)->post(route('questionnaires.finish', $questionnaire->slug));

        $this->actingAs($this->user)->get(route('date-night.history'))
            ->assertInertia(fn ($page) => $page->where('plans.data.0.is_solo', true)->where('plans.data.0.compatibility_score', null));

        $this->actingAs($this->user)->get(route('questionnaires.show', $questionnaire->slug))
            ->assertInertia(fn ($page) => $page->has('past_attempts', 0));
    }

    public function test_viewing_a_plan_twice_notifies_the_partner_once(): void
    {
        $questionnaire = $this->questionnaire();
        $plan = DateNightPlan::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $questionnaire->id])->id,
            'partner_two_response_id' => Response::factory()->completed()->create(['user_id' => $this->partner->id, 'questionnaire_id' => $questionnaire->id])->id,
        ]);

        $this->actingAs($this->user)->get(route('date-night.show', $plan))->assertOk();
        $this->actingAs($this->user)->get(route('date-night.show', $plan))->assertOk();

        $this->assertSame(1, AppNotification::where('user_id', $this->partner->id)->where('type', NotificationType::PartnerViewedPlan)->count());
    }

    public function test_the_questionnaire_reminder_is_sent_once_a_week_not_every_day(): void
    {
        $this->user->update(['last_completed_questionnaire_at' => null]);

        $this->artisan('datenight:send-reminders')->assertSuccessful();
        $this->artisan('datenight:send-reminders')->assertSuccessful();

        $this->assertSame(1, AppNotification::where('user_id', $this->user->id)->where('type', NotificationType::QuestionnaireReminder)->count());
    }

    public function test_search_and_timeline_include_the_partners_moments_and_link_to_them(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->partner->id, 'title' => 'Lighthouse picnic', 'date' => now()->subDay()]);
        $future = CalendarEvent::factory()->create(['user_id' => $this->user->id, 'title' => 'Future trip', 'date' => now()->addMonth()]);
        $past = CalendarEvent::factory()->create(['user_id' => $this->partner->id, 'title' => 'Past dinner', 'date' => now()->subDays(3)]);

        $results = app(GlobalSearchService::class)->search($this->user, 'Lighthouse');
        $this->assertSame(route('moments.show', $moment), $results['moments'][0]['url']);

        $timeline = app(TimelineBuilderService::class)->build($this->user, 'all');
        $titles = $timeline->pluck('title');

        $this->assertTrue($titles->contains('Lighthouse picnic'));
        $this->assertTrue($titles->contains('Past dinner'));
        $this->assertFalse($titles->contains('Future trip'));
        $this->assertSame(route('calendar.show', $past), $timeline->firstWhere('title', 'Past dinner')['link']);
        $this->assertSame($timeline->pluck('occurred_at')->sortDesc()->values()->all(), $timeline->pluck('occurred_at')->all());
        $this->assertNotNull($future->fresh());
    }

    public function test_the_monthly_stat_counts_only_this_month(): void
    {
        $questionnaire = $this->questionnaire();
        $this->user->update(['monthly_completion_count' => 40]);
        Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $questionnaire->id, 'completed_at' => now()]);
        Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $questionnaire->id, 'completed_at' => now()->subMonths(2)]);

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('stats.monthly_completions', 1));
    }

    public function test_achievement_progress_never_exceeds_one_hundred_percent(): void
    {
        $visible = Achievement::factory()->create(['hidden' => false]);
        $hidden = Achievement::factory()->create(['hidden' => true]);
        $this->user->achievements()->attach([$visible->id => ['unlocked_at' => now()], $hidden->id => ['unlocked_at' => now()]]);

        $this->actingAs($this->user)->get(route('relationship-hub.index'))
            ->assertInertia(fn ($page) => $page->where('achievement_progress.percentage', 100));
    }
}
