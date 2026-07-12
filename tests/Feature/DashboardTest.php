<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_users_without_onboarding_are_redirected(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('onboarding.index'));
    }

    public function test_dashboard_shows_partner_data_when_connected(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'partner_id' => $partner->id,
        ]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('partner')
        );
    }

    public function test_dashboard_shows_null_partner_when_not_connected(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true, 'partner_id' => null]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('partner', null)
        );
    }
}
