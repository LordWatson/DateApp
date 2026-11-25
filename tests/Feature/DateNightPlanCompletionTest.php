<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateNightPlanCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    private DateNightPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['display_name' => 'Alex']);
        $this->partner = User::factory()->create(['display_name' => 'Sam', 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        $questionnaire = Questionnaire::factory()->create(['is_solo' => false]);

        $this->plan = DateNightPlan::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => Response::factory()->completed()->create(['user_id' => $this->user->id, 'questionnaire_id' => $questionnaire->id])->id,
            'partner_two_response_id' => Response::factory()->completed()->create(['user_id' => $this->partner->id, 'questionnaire_id' => $questionnaire->id])->id,
            'theme' => 'Game Night',
        ]);
    }

    public function test_a_participant_rates_the_night_and_the_partner_is_told_once(): void
    {
        $this->actingAs($this->user)
            ->post(route('date-night.complete', $this->plan), ['rating' => 4, 'note' => 'Lovely'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('date_night_plan_completions', [
            'date_night_plan_id' => $this->plan->id,
            'user_id' => $this->user->id,
            'rating' => 4,
            'note' => 'Lovely',
        ]);

        // Changing the rating updates the same row and is not announced again.
        $this->actingAs($this->user)->post(route('date-night.complete', $this->plan), ['rating' => 5]);

        $this->assertDatabaseCount('date_night_plan_completions', 1);
        $this->assertDatabaseHas('date_night_plan_completions', ['user_id' => $this->user->id, 'rating' => 5, 'note' => null]);

        $notification = AppNotification::where('type', 'partner_completed_plan')->sole();
        $this->assertSame($this->partner->id, $notification->user_id);
        $this->assertSame($this->plan->id, $notification->data['plan_id']);
    }

    public function test_each_partner_has_their_own_rating_on_the_plan_page(): void
    {
        $this->actingAs($this->user)->post(route('date-night.complete', $this->plan), ['rating' => 4]);
        $this->actingAs($this->partner)->post(route('date-night.complete', $this->plan), ['rating' => 2, 'note' => 'Too loud']);

        $this->actingAs($this->user)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page
                ->where('plan.completion.mine.rating', 4)
                ->where('plan.completion.partner.rating', 2)
                ->where('plan.completion.partner.name', 'Sam')
                ->where('plan.completion.partner.note', 'Too loud')
            );
    }

    public function test_an_unrated_plan_has_no_completion(): void
    {
        $this->actingAs($this->user)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page
                ->where('plan.completion.mine', null)
                ->where('plan.completion.partner', null)
                ->where('plan.moments', [])
            );
    }

    public function test_a_stranger_cannot_rate_a_plan(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('date-night.complete', $this->plan), ['rating' => 4])
            ->assertForbidden();

        $this->assertDatabaseCount('date_night_plan_completions', 0);
    }

    public function test_the_rating_must_be_one_to_five_and_the_date_not_in_the_future(): void
    {
        $this->actingAs($this->user)->post(route('date-night.complete', $this->plan), ['rating' => 0])->assertSessionHasErrors('rating');
        $this->actingAs($this->user)->post(route('date-night.complete', $this->plan), ['rating' => 6])->assertSessionHasErrors('rating');
        $this->actingAs($this->user)->post(route('date-night.complete', $this->plan), [])->assertSessionHasErrors('rating');
        $this->actingAs($this->user)
            ->post(route('date-night.complete', $this->plan), ['rating' => 3, 'completed_on' => now()->addDays(2)->toDateString()])
            ->assertSessionHasErrors('completed_on');

        $this->assertDatabaseCount('date_night_plan_completions', 0);
    }

    public function test_history_marks_a_plan_anyone_has_done(): void
    {
        $this->actingAs($this->user)
            ->get(route('date-night.history'))
            ->assertInertia(fn ($page) => $page->where('plans.data.0.is_done', false));

        $this->actingAs($this->partner)->post(route('date-night.complete', $this->plan), ['rating' => 5]);

        $this->actingAs($this->user)
            ->get(route('date-night.history'))
            ->assertInertia(fn ($page) => $page->where('plans.data.0.is_done', true));
    }

    public function test_the_moments_page_opens_ready_to_capture_from_the_plan(): void
    {
        $this->actingAs($this->user)->post(route('date-night.complete', $this->plan), ['rating' => 4, 'completed_on' => '2026-01-10']);

        $this->actingAs($this->user)
            ->get(route('moments.index', ['plan' => $this->plan->id]))
            ->assertInertia(fn ($page) => $page->where('prefill', [
                'date_night_plan_id' => $this->plan->id,
                'title' => 'Game Night',
                'date' => '2026-01-10',
            ]));
    }

    public function test_someone_elses_plan_prefills_nothing(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('moments.index', ['plan' => $this->plan->id]))
            ->assertInertia(fn ($page) => $page->where('prefill', null));

        $this->actingAs($this->user)
            ->get(route('moments.index'))
            ->assertInertia(fn ($page) => $page->where('prefill', null));
    }

    public function test_a_moment_captured_from_the_plan_is_listed_on_it_for_both_partners(): void
    {
        $this->actingAs($this->user)
            ->post(route('moments.store'), [
                'title' => 'We won at Catan',
                'date' => '2026-01-10',
                'date_night_plan_id' => $this->plan->id,
            ])
            ->assertRedirect();
        Moment::factory()->create(['date_night_plan_id' => $this->plan->id, 'title' => "A stranger's"]);

        $this->actingAs($this->partner)
            ->get(route('date-night.show', $this->plan))
            ->assertInertia(fn ($page) => $page
                ->has('plan.moments', 1)
                ->where('plan.moments.0.title', 'We won at Catan')
            );
    }
}
