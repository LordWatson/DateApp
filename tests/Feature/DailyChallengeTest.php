<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Events\ChallengeCompleted;
use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\Challenge;
use App\Models\DailyChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DailyChallengeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->partner = User::factory()->create(['onboarding_completed' => true, 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);
    }

    private function dashboardChallenge(User $user): ?array
    {
        // Fresh: the streak is written to the database, not to the instance the test holds.
        return $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk()->inertiaProps('todayChallenge');
    }

    public function test_dashboard_shows_the_same_challenge_all_day(): void
    {
        Challenge::factory()->count(20)->create();

        $first = $this->dashboardChallenge($this->user);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first['id'], $this->dashboardChallenge($this->user)['id']);
        }

        $this->assertSame($first['id'], $this->dashboardChallenge($this->partner)['id']);
        $this->assertSame(1, DailyChallenge::count());
    }

    public function test_only_active_non_archived_challenges_are_picked(): void
    {
        Challenge::factory()->count(5)->create(['active' => false]);
        Challenge::factory()->count(5)->create(['archived' => true]);

        $this->assertNull($this->dashboardChallenge($this->user));

        $eligible = Challenge::factory()->create();

        $this->assertSame($eligible->id, $this->dashboardChallenge($this->user)['id']);
    }

    public function test_completing_records_once_and_shows_partner_state(): void
    {
        Challenge::factory()->create();

        $before = $this->dashboardChallenge($this->user);
        $this->assertFalse($before['completed_by_me']);
        $this->assertFalse($before['completed_by_partner']);

        $this->actingAs($this->user)->post(route('daily-challenge.complete'))->assertRedirect();
        $this->actingAs($this->user)->post(route('daily-challenge.complete'))->assertRedirect();

        $this->assertDatabaseCount('daily_challenge_completions', 1);
        $this->assertDatabaseHas('daily_challenge_completions', [
            'user_id' => $this->user->id,
            'daily_challenge_id' => DailyChallenge::sole()->id,
        ]);

        $mine = $this->dashboardChallenge($this->user);
        $this->assertTrue($mine['completed_by_me']);
        $this->assertFalse($mine['completed_by_partner']);

        $theirs = $this->dashboardChallenge($this->partner);
        $this->assertFalse($theirs['completed_by_me']);
        $this->assertTrue($theirs['completed_by_partner']);
    }

    public function test_completing_notifies_the_partner_and_unlocks_the_achievement_once(): void
    {
        $challenge = Challenge::factory()->create();
        $achievement = Achievement::factory()->create([
            'unlock_condition' => 'challenges_completed',
            'unlock_value' => 1,
        ]);

        $this->actingAs($this->user)->post(route('daily-challenge.complete'));
        $this->actingAs($this->user)->post(route('daily-challenge.complete'));

        $notification = AppNotification::where('type', NotificationType::DailyChallenge)->sole();
        $this->assertSame($this->partner->id, $notification->user_id);
        $this->assertSame($challenge->title, $notification->body);

        $this->assertTrue($this->user->achievements()->whereKey($achievement->id)->exists());
        $this->assertFalse($this->partner->achievements()->exists());
    }

    public function test_event_is_dispatched_only_for_the_first_completion(): void
    {
        Challenge::factory()->create();
        Event::fake([ChallengeCompleted::class]);

        $this->actingAs($this->user)->post(route('daily-challenge.complete'));
        $this->actingAs($this->user)->post(route('daily-challenge.complete'));

        Event::assertDispatchedTimes(ChallengeCompleted::class, 1);
    }

    public function test_a_new_day_can_be_completed_again(): void
    {
        Challenge::factory()->count(2)->create();

        $this->actingAs($this->user)->post(route('daily-challenge.complete'));
        $this->travel(1)->days();
        $this->actingAs($this->user)->post(route('daily-challenge.complete'));

        $this->assertDatabaseCount('daily_challenge_completions', 2);
    }

    public function test_the_couple_streak_only_grows_on_days_both_partners_finish(): void
    {
        Challenge::factory()->count(3)->create();

        $this->actingAs($this->user)->post(route('daily-challenge.complete'));
        $this->assertSame(0, $this->dashboardChallenge($this->user)['couple_streak']);

        $this->actingAs($this->partner)->post(route('daily-challenge.complete'));
        // A second tap must not count the day twice.
        $this->actingAs($this->partner)->post(route('daily-challenge.complete'));

        $this->assertSame(1, $this->dashboardChallenge($this->user)['couple_streak']);
        $this->assertSame(1, $this->dashboardChallenge($this->partner)['couple_streak']);

        $this->travel(1)->days();
        $this->actingAs($this->partner)->post(route('daily-challenge.complete'));
        $this->actingAs($this->user)->post(route('daily-challenge.complete'));

        $this->assertSame(2, $this->dashboardChallenge($this->user)['couple_streak']);
        $this->assertSame(2, $this->user->fresh()->longest_challenge_streak);
        $this->assertSame(2, $this->partner->fresh()->longest_challenge_streak);
    }

    public function test_a_missed_day_ends_the_couple_streak_and_the_next_shared_day_starts_at_one(): void
    {
        Challenge::factory()->count(3)->create();
        $this->bothComplete();

        $this->travel(2)->days();

        $this->assertSame(0, $this->dashboardChallenge($this->user)['couple_streak']);

        $this->bothComplete();

        $this->assertSame(1, $this->dashboardChallenge($this->user)['couple_streak']);
        $this->assertSame(1, $this->user->fresh()->longest_challenge_streak);
    }

    public function test_disconnecting_resets_the_couple_streak_but_keeps_the_best(): void
    {
        Challenge::factory()->create();
        $this->bothComplete();

        $this->actingAs($this->user)->delete(route('partner.disconnect'), ['keep_plans' => true, 'keep_notes' => true, 'password' => 'password']);

        foreach ([$this->user->fresh(), $this->partner->fresh()] as $person) {
            $this->assertSame(0, $person->challenge_streak);
            $this->assertNull($person->challenge_streak_date);
            $this->assertSame(1, $person->longest_challenge_streak);
        }
    }

    public function test_someone_without_a_partner_has_no_couple_streak(): void
    {
        Challenge::factory()->create();
        $loner = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($loner)->post(route('daily-challenge.complete'));

        $this->assertNull($this->dashboardChallenge($loner)['couple_streak']);
        $this->assertSame(0, $loner->fresh()->challenge_streak);
    }

    public function test_the_couple_streak_achievement_unlocks_for_both_partners(): void
    {
        Challenge::factory()->count(3)->create();
        $achievement = Achievement::factory()->create([
            'unlock_condition' => 'challenge_streak_days',
            'unlock_value' => 2,
        ]);

        $this->bothComplete();
        $this->assertFalse($this->user->achievements()->whereKey($achievement->id)->exists());

        $this->travel(1)->days();
        $this->bothComplete();

        $this->assertTrue($this->user->achievements()->whereKey($achievement->id)->exists());
        $this->assertTrue($this->partner->achievements()->whereKey($achievement->id)->exists());
    }

    private function bothComplete(): void
    {
        $this->actingAs($this->user)->post(route('daily-challenge.complete'));
        $this->actingAs($this->partner)->post(route('daily-challenge.complete'));
    }

    public function test_completing_with_no_challenge_available_does_nothing(): void
    {
        $this->actingAs($this->user)->post(route('daily-challenge.complete'))->assertRedirect();

        $this->assertDatabaseCount('daily_challenge_completions', 0);
    }
}
