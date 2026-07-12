<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_insights(): void
    {
        $response = $this->actingAs($this->user)->get(route('insights.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('insights/Index'));
    }

    public function test_insights_contains_required_keys(): void
    {
        $response = $this->actingAs($this->user)->get(route('insights.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('insights/Index')
            ->has('insights.total_questionnaires')
            ->has('insights.average_compatibility')
            ->has('insights.current_streak')
            ->has('insights.longest_streak')
            ->has('insights.love_notes_sent')
            ->has('insights.moments_count')
            ->has('insights.calendar_events_count')
        );
    }

    public function test_relationship_hub_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('relationship-hub.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('relationship-hub/Index'));
    }
}
