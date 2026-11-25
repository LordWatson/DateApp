<?php

namespace Tests\Feature;

use App\Mail\MomentCreatedMail;
use App\Models\AppNotification;
use App\Models\CalendarEvent;
use App\Models\Challenge;
use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConnectionPauseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['display_name' => 'Alex', 'timezone' => 'UTC']);
        $this->partner = User::factory()->create(['display_name' => 'Sam', 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);
    }

    public function test_pausing_marks_both_partners_and_tells_the_other_once(): void
    {
        $this->actingAs($this->user)->post(route('partner.pause'))->assertSessionHas('success');
        $this->actingAs($this->user->fresh())->post(route('partner.pause'))->assertSessionHas('error');

        foreach ([$this->user->fresh(), $this->partner->fresh()] as $person) {
            $this->assertTrue($person->isConnectionPaused());
            $this->assertSame($this->user->id, $person->connection_paused_by);
            $this->assertNotNull($person->partner_id);
        }

        $notification = AppNotification::sole();
        $this->assertSame($this->partner->id, $notification->user_id);
        $this->assertSame('connection_paused', $notification->type->value);
    }

    public function test_the_pages_say_the_connection_is_paused(): void
    {
        $this->pause();

        $this->actingAs($this->user->fresh())
            ->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page->where('paused.by_me', true)->has('partner'));

        $this->actingAs($this->partner->fresh())
            ->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page->where('paused.by_me', false));

        $this->actingAs($this->user->fresh())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('connectionPaused', true));
    }

    public function test_a_love_note_cannot_be_sent_while_paused(): void
    {
        $this->pause();

        $this->actingAs($this->partner->fresh())
            ->post(route('love-notes.store'), ['message' => 'Hello'])
            ->assertSessionHasErrors('partner');

        $this->assertDatabaseCount('love_notes', 0);
    }

    public function test_nothing_is_sent_to_the_partner_about_new_activity_while_paused(): void
    {
        Mail::fake();
        Challenge::factory()->create();
        $plan = $this->sharedPlan();
        $this->pause();
        AppNotification::query()->delete();
        $user = $this->user->fresh();

        $this->actingAs($user)->post(route('moments.store'), ['title' => 'A walk', 'date' => now()->toDateString()]);
        $this->actingAs($user)->post(route('daily-challenge.complete'));
        $this->actingAs($user)->post(route('date-night.toggle-like', $plan));
        $this->actingAs($user)->get(route('date-night.show', $plan))->assertOk();

        $this->assertSame(0, AppNotification::where('user_id', $this->partner->id)->count());
        Mail::assertNotQueued(MomentCreatedMail::class);
    }

    public function test_a_calendar_reminder_goes_to_the_owner_only_while_paused(): void
    {
        CalendarEvent::factory()->create(['user_id' => $this->user->id, 'date' => now()->toDateString(), 'reminder' => 'same_day']);
        $this->pause();
        AppNotification::query()->delete();

        $this->artisan('datenight:send-calendar-reminders')->assertSuccessful();

        $this->assertSame([$this->user->id], AppNotification::pluck('user_id')->all());
    }

    public function test_planning_together_is_on_hold_while_paused(): void
    {
        $questionnaire = Questionnaire::factory()->create(['is_solo' => true, 'partner_optional' => true]);
        $this->pause();

        $this->actingAs($this->user->fresh())
            ->post(route('questionnaires.start', $questionnaire->slug), ['mode' => 'couple'])
            ->assertSessionHasErrors('mode');
    }

    public function test_shared_history_stays_visible_to_both_while_paused(): void
    {
        $plan = $this->sharedPlan();
        $moment = Moment::factory()->create(['user_id' => $this->partner->id]);
        $this->pause();

        $this->actingAs($this->user->fresh())->get(route('date-night.show', $plan))->assertOk();
        $this->actingAs($this->partner->fresh())->get(route('date-night.show', $plan))->assertOk();
        $this->actingAs($this->user->fresh())->get(route('moments.show', $moment))->assertOk();
    }

    public function test_only_the_person_who_paused_can_resume(): void
    {
        $this->pause();
        AppNotification::query()->delete();

        $this->actingAs($this->partner->fresh())->post(route('partner.resume'))->assertSessionHas('error');
        $this->assertTrue($this->user->fresh()->isConnectionPaused());

        $this->actingAs($this->user->fresh())->post(route('partner.resume'))->assertSessionHas('success');

        foreach ([$this->user->fresh(), $this->partner->fresh()] as $person) {
            $this->assertFalse($person->isConnectionPaused());
            $this->assertNull($person->connection_paused_by);
        }

        $notification = AppNotification::sole();
        $this->assertSame($this->partner->id, $notification->user_id);
        $this->assertSame('connection_resumed', $notification->type->value);
    }

    public function test_the_couple_streak_is_frozen_during_a_pause_and_carries_on_after(): void
    {
        Challenge::factory()->count(3)->create();
        $this->bothCompleteTheChallenge();
        $this->pause();

        $this->travel(5)->days();
        // Completions during the pause do not count towards the shared streak.
        $this->bothCompleteTheChallenge();

        $this->assertSame(1, $this->user->fresh()->currentChallengeStreak());
        $this->assertSame(1, $this->user->fresh()->challenge_streak);

        $this->travel(1)->days();
        $this->actingAs($this->user->fresh())->post(route('partner.resume'));
        $this->bothCompleteTheChallenge();

        $this->assertSame(2, $this->user->fresh()->currentChallengeStreak());
        $this->assertSame(2, $this->partner->fresh()->currentChallengeStreak());
    }

    public function test_disconnecting_clears_a_pause(): void
    {
        $this->pause();

        $this->actingAs($this->partner->fresh())
            ->delete(route('partner.disconnect'), ['keep_plans' => true, 'keep_notes' => true, 'password' => 'password'])
            ->assertRedirect(route('partner.index'));

        foreach ([$this->user->fresh(), $this->partner->fresh()] as $person) {
            $this->assertNull($person->partner_id);
            $this->assertNull($person->connection_paused_at);
            $this->assertNull($person->connection_paused_by);
        }
    }

    public function test_an_admin_disconnect_clears_a_pause_too(): void
    {
        $this->pause();
        $admin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);

        $this->actingAs($admin)->post(route('admin.couples.disconnect', $this->user))->assertRedirect();

        $this->assertNull($this->user->fresh()->connection_paused_at);
        $this->assertNull($this->partner->fresh()->connection_paused_at);
        $this->assertNull($this->partner->fresh()->partner_id);
    }

    public function test_someone_without_a_partner_has_nothing_to_pause(): void
    {
        $this->actingAs(User::factory()->create())->post(route('partner.pause'))->assertSessionHas('error');
    }

    private function pause(): void
    {
        $this->actingAs($this->user->fresh())->post(route('partner.pause'));
    }

    private function bothCompleteTheChallenge(): void
    {
        $this->actingAs($this->user->fresh())->post(route('daily-challenge.complete'));
        $this->actingAs($this->partner->fresh())->post(route('daily-challenge.complete'));
    }

    private function sharedPlan(): DateNightPlan
    {
        return DateNightPlan::factory()->create([
            'partner_one_response_id' => Response::factory()->completed()->create(['user_id' => $this->user->id])->id,
            'partner_two_response_id' => Response::factory()->completed()->create(['user_id' => $this->partner->id])->id,
        ]);
    }
}
