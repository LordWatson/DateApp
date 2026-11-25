<?php

namespace Tests\Feature\Commands;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendCalendarRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-12-01 10:00:00');

        $this->owner = User::factory()->create(['onboarding_completed' => true, 'timezone' => 'UTC']);
        $this->partner = User::factory()->create(['onboarding_completed' => true, 'partner_id' => $this->owner->id]);
        $this->owner->update(['partner_id' => $this->partner->id]);
    }

    private function event(array $attributes): CalendarEvent
    {
        return CalendarEvent::factory()->create(['user_id' => $this->owner->id, ...$attributes]);
    }

    private function reminderCount(): int
    {
        return AppNotification::where('type', NotificationType::CalendarReminder)->count();
    }

    public function test_due_reminder_is_sent_once_to_owner_and_partner(): void
    {
        $event = $this->event(['title' => 'Dinner', 'date' => '2026-12-02', 'time' => '19:30:00', 'reminder' => '1_day']);

        $this->artisan('datenight:send-calendar-reminders')->assertSuccessful();
        $this->artisan('datenight:send-calendar-reminders')->assertSuccessful();

        $this->assertSame(2, $this->reminderCount());

        foreach ([$this->owner, $this->partner] as $recipient) {
            $notification = AppNotification::where('user_id', $recipient->id)->sole();
            $this->assertSame('Dinner is tomorrow at 19:30.', $notification->body);
            $this->assertSame($event->id, $notification->data['calendar_event_id']);
        }

        $this->assertTrue($event->fresh()->reminder_sent);
    }

    public function test_reminder_is_not_sent_before_it_is_due(): void
    {
        $event = $this->event(['date' => '2026-12-05', 'reminder' => '3_days']);

        $this->artisan('datenight:send-calendar-reminders');
        $this->assertSame(0, $this->reminderCount());

        $this->travelTo('2026-12-02 00:30:00');
        $this->artisan('datenight:send-calendar-reminders');

        $this->assertSame(2, $this->reminderCount());
        $this->assertTrue($event->fresh()->reminder_sent);
    }

    public function test_nothing_is_sent_without_a_reminder_or_for_a_past_event(): void
    {
        $this->event(['date' => '2026-12-01', 'reminder' => null]);
        $this->event(['date' => '2026-11-20', 'reminder' => 'same_day']);

        $this->artisan('datenight:send-calendar-reminders');

        $this->assertSame(0, $this->reminderCount());
    }

    public function test_same_day_reminder_follows_the_owners_timezone(): void
    {
        // 10:00 UTC on 1 December is already 2 December in Kiritimati (UTC+14).
        $this->owner->update(['timezone' => 'Pacific/Kiritimati']);
        $this->event(['date' => '2026-12-02', 'reminder' => 'same_day']);

        $this->artisan('datenight:send-calendar-reminders');

        $this->assertSame(2, $this->reminderCount());
    }

    public function test_moving_the_event_allows_a_new_reminder(): void
    {
        $event = $this->event(['date' => '2026-12-01', 'reminder' => 'same_day']);
        $this->artisan('datenight:send-calendar-reminders');
        $this->assertSame(2, $this->reminderCount());

        $payload = ['title' => $event->title, 'type' => 'custom', 'date' => '2026-12-01', 'reminder' => 'same_day'];

        // Saving without touching the date or reminder does not re-arm it.
        $this->actingAs($this->owner)->put(route('calendar.update', $event), $payload);
        $this->assertTrue($event->fresh()->reminder_sent);

        $this->actingAs($this->owner)->put(route('calendar.update', $event), ['date' => '2026-12-10', 'reminder' => '1_week'] + $payload);
        $this->assertFalse($event->fresh()->reminder_sent);

        $this->travelTo('2026-12-03 10:00:00');
        $this->artisan('datenight:send-calendar-reminders');

        $this->assertSame(4, $this->reminderCount());
    }
}
