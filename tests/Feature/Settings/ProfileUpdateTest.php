<?php

namespace Tests\Feature\Settings;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_display_name_avatar_timezone_and_gender_can_be_updated()
    {
        $user = User::factory()->create(['display_name' => null, 'avatar' => null, 'timezone' => 'UTC', 'gender' => null]);

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'display_name' => 'Lexi',
                'avatar' => '🦊',
                'timezone' => 'Europe/London',
                'gender' => Gender::Other->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Lexi', $user->display_name);
        $this->assertSame('🦊', $user->avatar);
        $this->assertSame('Europe/London', $user->timezone);
        $this->assertSame(Gender::Other, $user->gender);
    }

    public function test_invalid_profile_details_are_rejected()
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'display_name' => str_repeat('a', 51),
                'avatar' => str_repeat('a', 256),
                'timezone' => 'Mars/Olympus_Mons',
                'gender' => 'robot',
            ])
            ->assertSessionHasErrors(['display_name', 'avatar', 'timezone', 'gender']);

        $this->assertSame('UTC', $user->refresh()->timezone);
    }

    public function test_onboarding_rejects_an_avatar_longer_than_the_column()
    {
        $user = User::factory()->create(['onboarding_completed' => false]);

        $this
            ->actingAs($user)
            ->post(route('onboarding.complete-profile'), [
                'display_name' => 'Lexi',
                'gender' => Gender::Other->value,
                'timezone' => 'UTC',
                'avatar' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('avatar');
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}
