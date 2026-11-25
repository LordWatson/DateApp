<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-06-10 12:00:00');

        $this->user = User::factory()->create(['timezone' => 'UTC']);
    }

    public function test_a_yearly_event_is_listed_at_its_next_occurrence(): void
    {
        $this->event(['title' => 'Anniversary', 'date' => '2023-06-20', 'recurrence' => 'yearly']);

        $this->actingAs($this->user)
            ->get(route('calendar.index'))
            ->assertInertia(fn ($page) => $page
                ->where('events.0.date', '2023-06-20')
                ->where('events.0.recurrence', 'yearly')
                ->where('events.0.next_date', '2026-06-20')
                ->where('events.0.days_until', 10)
                ->where('events.0.occurrence', 3)
            );
    }

    public function test_a_yearly_event_that_has_passed_this_year_moves_to_next_year(): void
    {
        $this->event(['date' => '2020-03-01', 'recurrence' => 'yearly']);

        $this->actingAs($this->user)
            ->get(route('calendar.index'))
            ->assertInertia(fn ($page) => $page
                ->where('events.0.next_date', '2027-03-01')
                ->where('events.0.occurrence', 7)
            );
    }

    public function test_a_yearly_event_falling_today_counts_as_today(): void
    {
        $this->event(['date' => '2022-06-10', 'recurrence' => 'yearly']);

        $this->actingAs($this->user)
            ->get(route('calendar.index'))
            ->assertInertia(fn ($page) => $page->where('events.0.next_date', '2026-06-10')->where('events.0.days_until', 0));
    }

    public function test_a_leap_day_event_falls_on_the_28th_in_other_years(): void
    {
        $this->event(['date' => '2024-02-29', 'recurrence' => 'yearly']);

        $this->actingAs($this->user)
            ->get(route('calendar.index'))
            ->assertInertia(fn ($page) => $page->where('events.0.next_date', '2027-02-28'));
    }

    public function test_a_one_off_event_stays_on_its_own_date(): void
    {
        $this->event(['date' => '2025-01-05']);

        $this->actingAs($this->user)
            ->get(route('calendar.index'))
            ->assertInertia(fn ($page) => $page
                ->where('events.0.next_date', '2025-01-05')
                ->where('events.0.recurrence', null)
                ->where('events.0.occurrence', null)
                ->where('events.0.days_until', -521)
            );
    }

    public function test_events_are_ordered_by_when_they_next_fall(): void
    {
        $this->event(['title' => 'One-off in July', 'date' => '2026-07-01']);
        $this->event(['title' => 'Old birthday in June', 'date' => '1990-06-15', 'recurrence' => 'yearly']);

        $this->actingAs($this->user)
            ->get(route('calendar.index'))
            ->assertInertia(fn ($page) => $page
                ->where('events.0.title', 'Old birthday in June')
                ->where('events.1.title', 'One-off in July')
            );
    }

    public function test_the_hub_lists_a_repeating_event_from_an_earlier_year(): void
    {
        $this->event(['title' => 'Anniversary', 'date' => '2023-06-20', 'recurrence' => 'yearly']);
        $this->event(['title' => 'Long gone', 'date' => '2023-06-21']);

        $this->actingAs($this->user)
            ->get(route('relationship-hub.index'))
            ->assertInertia(fn ($page) => $page
                ->has('upcoming_events', 1)
                ->where('upcoming_events.0.title', 'Anniversary')
                ->where('upcoming_events.0.date', '2026-06-20')
            );
    }

    public function test_recurrence_can_be_saved_and_must_be_a_known_value(): void
    {
        $this->actingAs($this->user)
            ->post(route('calendar.store'), ['title' => 'Birthday', 'type' => 'birthday', 'date' => '2026-08-01', 'recurrence' => 'yearly'])
            ->assertRedirect();

        $this->assertDatabaseHas('calendar_events', ['title' => 'Birthday', 'recurrence' => 'yearly']);

        $this->actingAs($this->user)
            ->post(route('calendar.store'), ['title' => 'Weekly', 'type' => 'custom', 'date' => '2026-08-01', 'recurrence' => 'weekly'])
            ->assertSessionHasErrors('recurrence');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(array $attributes): CalendarEvent
    {
        return CalendarEvent::factory()->create(['user_id' => $this->user->id, ...$attributes]);
    }
}
