<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_calendar(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('calendar/Index'));
    }

    public function test_user_can_create_calendar_event(): void
    {
        $response = $this->actingAs($this->user)->post(route('calendar.store'), [
            'title' => 'Date Night',
            'type' => 'date_night',
            'date' => '2026-08-01',
            'colour' => '#EC4899',
        ]);

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $this->user->id,
            'title' => 'Date Night',
            'type' => 'date_night',
        ]);
    }

    public function test_user_can_update_own_event(): void
    {
        $event = CalendarEvent::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('calendar.update', $event), [
            'title' => 'Updated Title',
            'type' => 'custom',
            'date' => '2026-09-01',
            'colour' => '#9333EA',
        ]);

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseHas('calendar_events', ['id' => $event->id, 'title' => 'Updated Title']);
    }

    public function test_user_cannot_update_others_event(): void
    {
        $other = User::factory()->create();
        $event = CalendarEvent::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->put(route('calendar.update', $event), [
            'title' => 'Hacked',
            'type' => 'custom',
            'date' => '2026-09-01',
            'colour' => '#000000',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_own_event(): void
    {
        $event = CalendarEvent::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('calendar.destroy', $event));

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    public function test_user_cannot_delete_others_event(): void
    {
        $other = User::factory()->create();
        $event = CalendarEvent::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->delete(route('calendar.destroy', $event));

        $response->assertForbidden();
    }
}
