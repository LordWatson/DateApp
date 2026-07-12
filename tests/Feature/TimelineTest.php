<?php

namespace Tests\Feature;

use App\Models\Moment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_timeline(): void
    {
        $response = $this->actingAs($this->user)->get(route('timeline.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('timeline/Index'));
    }

    public function test_timeline_accepts_filter(): void
    {
        $response = $this->actingAs($this->user)->get(route('timeline.index', ['filter' => 'moments']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('timeline/Index')
            ->where('filter', 'moments')
        );
    }

    public function test_timeline_shows_moments(): void
    {
        Moment::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Sunset Walk',
        ]);

        $response = $this->actingAs($this->user)->get(route('timeline.index', ['filter' => 'moments']));

        $response->assertInertia(fn ($page) => $page
            ->component('timeline/Index')
            ->has('entries', 1)
        );
    }
}
