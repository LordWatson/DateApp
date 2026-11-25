<?php

namespace Tests\Feature;

use App\Actions\UpdateStreakAction;
use App\Models\CalendarEvent;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The app runs in UTC. In November Auckland is 13 hours ahead and Los Angeles 8 hours behind.
 */
class UserTimezoneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userIn(string $timezone, array $attributes = []): User
    {
        return User::factory()->create(['onboarding_completed' => true, 'timezone' => $timezone, ...$attributes]);
    }

    public function test_today_is_the_users_own_day(): void
    {
        $this->travelTo('2026-11-25 12:00:00');

        $this->assertSame('2026-11-26', $this->userIn('Pacific/Auckland')->today()->toDateString());
        $this->assertSame('2026-11-25', $this->userIn('America/Los_Angeles')->today()->toDateString());
        $this->assertSame('2026-11-25', $this->userIn('UTC')->today()->toDateString());
    }

    public function test_streak_grows_when_the_local_day_changes_inside_one_utc_day(): void
    {
        // 09:00 on the 25th in Auckland.
        $user = $this->userIn('Pacific/Auckland', [
            'current_streak' => 3,
            'longest_streak' => 3,
            'last_completed_questionnaire_at' => '2026-11-24 20:00:00',
        ]);

        // 23:30 on the 25th in Auckland: the same local day, so the streak stays.
        $this->travelTo('2026-11-25 10:30:00');
        app(UpdateStreakAction::class)->execute($user);
        $this->assertSame(3, $user->fresh()->current_streak);

        // 00:30 on the 26th in Auckland: the next local day, although UTC is still on the 25th.
        $this->travelTo('2026-11-25 11:30:00');
        app(UpdateStreakAction::class)->execute($user->fresh());
        $this->assertSame(4, $user->fresh()->current_streak);
    }

    public function test_streak_does_not_grow_twice_in_one_local_day_across_utc_midnight(): void
    {
        // 15:00 on the 24th in Los Angeles.
        $user = $this->userIn('America/Los_Angeles', [
            'current_streak' => 3,
            'longest_streak' => 3,
            'last_completed_questionnaire_at' => '2026-11-24 23:00:00',
        ]);

        // 17:00 on the 24th in Los Angeles, already the 25th in UTC.
        $this->travelTo('2026-11-25 01:00:00');
        app(UpdateStreakAction::class)->execute($user);

        $this->assertSame(3, $user->fresh()->current_streak);
    }

    public function test_streak_evaluation_uses_the_users_own_day(): void
    {
        // 16:05 on the 25th in Los Angeles.
        $this->travelTo('2026-11-26 00:05:00');

        // Last done at 18:00 on the 24th local time: yesterday for them, so the streak is alive.
        $alive = $this->userIn('America/Los_Angeles', [
            'current_streak' => 5,
            'last_completed_questionnaire_at' => '2026-11-25 02:00:00',
        ]);
        // Last done at 18:00 on the 23rd local time: a whole local day was missed.
        $broken = $this->userIn('America/Los_Angeles', [
            'current_streak' => 5,
            'last_completed_questionnaire_at' => '2026-11-24 02:00:00',
        ]);

        $this->artisan('datenight:evaluate-streaks')->assertSuccessful();

        $this->assertSame(5, $alive->fresh()->current_streak);
        $this->assertSame(0, $broken->fresh()->current_streak);
    }

    public function test_calendar_and_moments_use_the_users_today(): void
    {
        $this->travelTo('2026-11-25 12:00:00');
        $user = $this->userIn('Pacific/Auckland');

        $this->actingAs($user)->get(route('calendar.index'))
            ->assertInertia(fn (Assert $page) => $page->where('today', '2026-11-26'));
        $this->actingAs($user)->get(route('moments.index'))
            ->assertInertia(fn (Assert $page) => $page->where('today', '2026-11-26'));
    }

    public function test_relationship_hub_hides_events_that_are_past_for_the_user(): void
    {
        $this->travelTo('2026-11-25 12:00:00');
        $user = $this->userIn('Pacific/Auckland');

        // Still "today" in UTC, already yesterday in Auckland.
        CalendarEvent::factory()->create(['user_id' => $user->id, 'date' => '2026-11-25', 'title' => 'Yesterday']);
        CalendarEvent::factory()->create(['user_id' => $user->id, 'date' => '2026-11-26', 'title' => 'Today']);

        $this->actingAs($user)->get(route('relationship-hub.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming_events', 1)
                ->where('upcoming_events.0.title', 'Today'));
    }

    public function test_dashboard_counts_this_month_in_the_users_timezone(): void
    {
        // 01:00 on 1 December in Auckland, still 30 November in UTC.
        $this->travelTo('2026-11-30 12:00:00');
        $user = $this->userIn('Pacific/Auckland');

        // 23:30 on 30 November in Auckland: last month for the user.
        Response::factory()->completed()->create(['user_id' => $user->id, 'completed_at' => '2026-11-30 10:30:00']);
        // 00:30 on 1 December in Auckland: this month for the user.
        Response::factory()->completed()->create(['user_id' => $user->id, 'completed_at' => '2026-11-30 11:30:00']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.monthly_completions', 1));
    }
}
