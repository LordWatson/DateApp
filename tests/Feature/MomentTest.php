<?php

namespace Tests\Feature;

use App\Models\Moment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MomentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_moments(): void
    {
        $response = $this->actingAs($this->user)->get(route('moments.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('moments/Index'));
    }

    public function test_user_can_create_moment(): void
    {
        $response = $this->actingAs($this->user)->post(route('moments.store'), [
            'title' => 'First Picnic',
            'description' => 'A beautiful day in the park.',
            'date' => '2026-07-01',
            'is_favourite' => false,
            'tags' => ['picnic', 'summer'],
        ]);

        $response->assertRedirect(route('moments.index'));
        $this->assertDatabaseHas('moments', [
            'user_id' => $this->user->id,
            'title' => 'First Picnic',
        ]);
    }

    public function test_user_can_delete_own_moment(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('moments.destroy', $moment));

        $response->assertRedirect(route('moments.index'));
        $this->assertDatabaseMissing('moments', ['id' => $moment->id]);
    }

    public function test_user_cannot_delete_others_moment(): void
    {
        $other = User::factory()->create();
        $moment = Moment::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->delete(route('moments.destroy', $moment));

        $response->assertForbidden();
    }

    public function test_user_can_toggle_favourite(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id, 'is_favourite' => false]);

        $this->actingAs($this->user)->post(route('moments.toggle-favourite', $moment));

        $this->assertDatabaseHas('moments', ['id' => $moment->id, 'is_favourite' => true]);
    }
}
