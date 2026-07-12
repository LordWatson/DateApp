<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_redirected_to_onboarding_after_registration(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Alex Smith',
            'email' => 'alex@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'alex@example.com')->first();
        $this->assertFalse((bool) $user->onboarding_completed);
    }

    public function test_onboarding_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->get(route('onboarding.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('onboarding/Index'));
    }

    public function test_completed_onboarding_users_are_redirected_from_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $this->actingAs($user);

        $response = $this->get(route('onboarding.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_complete_profile_during_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete-profile'), [
            'display_name' => 'Alex',
            'gender' => 'male',
            'timezone' => 'UTC',
            'avatar' => '🐱',
        ]);

        $response->assertRedirect(route('onboarding.index'));
        $this->assertEquals('Alex', $user->fresh()->display_name);
        $this->assertEquals('🐱', $user->fresh()->avatar);
    }

    public function test_profile_completion_requires_display_name(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete-profile'), [
            'display_name' => '',
            'gender' => 'male',
            'timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors('display_name');
    }

    public function test_profile_completion_requires_gender(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete-profile'), [
            'display_name' => 'Alex',
            'gender' => '',
            'timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_user_can_complete_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'));

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($user->fresh()->onboarding_completed);
    }

    public function test_guests_cannot_access_onboarding(): void
    {
        $response = $this->get(route('onboarding.index'));
        $response->assertRedirect(route('login'));
    }
}
