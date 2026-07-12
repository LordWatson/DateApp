<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Moment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_search(): void
    {
        $response = $this->actingAs($this->user)->get(route('search.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('search/Index'));
    }

    public function test_search_returns_empty_for_short_query(): void
    {
        $response = $this->actingAs($this->user)->get(route('search.index', ['q' => 'a']));
        $response->assertInertia(fn ($page) => $page
            ->component('search/Index')
            ->where('results', [])
        );
    }

    public function test_search_finds_moments(): void
    {
        Moment::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Romantic Sunset Walk',
        ]);

        $response = $this->actingAs($this->user)->get(route('search.index', ['q' => 'Sunset']));

        $response->assertInertia(fn ($page) => $page
            ->component('search/Index')
            ->has('results.moments', 1)
        );
    }

    public function test_search_finds_calendar_events(): void
    {
        CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Anniversary Dinner',
        ]);

        $response = $this->actingAs($this->user)->get(route('search.index', ['q' => 'Anniversary']));

        $response->assertInertia(fn ($page) => $page
            ->component('search/Index')
            ->has('results.calendar_events', 1)
        );
    }
}
