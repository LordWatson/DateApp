<?php

namespace Tests\Feature;

use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_users_who_have_not_finished_onboarding_can_read_the_legal_pages(): void
    {
        config(['app.legal.operator' => 'Example Ltd', 'app.legal.contact_email' => 'hello@example.test', 'ai.provider' => 'anthropic']);

        $this->get(route('privacy'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('legal/Privacy')
            ->where('operator', 'Example Ltd')
            ->where('contactEmail', 'hello@example.test')
            ->where('aiProvider', 'Anthropic (Claude)'));

        $this->actingAs(User::factory()->create(['onboarding_completed' => false]))
            ->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('legal/Terms')->where('operator', 'Example Ltd'));
    }

    public function test_the_legal_pages_stay_up_during_maintenance(): void
    {
        FeatureFlag::create(['key' => 'maintenance_mode', 'label' => 'Maintenance', 'enabled' => true, 'group' => 'system']);

        $this->get(route('privacy'))->assertOk();
        $this->get(route('terms'))->assertOk();
    }

    public function test_registration_requires_agreeing_to_the_terms(): void
    {
        $details = ['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'a-Long-password-123!', 'password_confirmation' => 'a-Long-password-123!'];

        $this->post(route('register.store'), $details)->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);

        $this->post(route('register.store'), [...$details, 'terms' => 'on'])->assertSessionHasNoErrors();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertNotNull($user->terms_accepted_at);
        // A new account has not been asked about AI yet, so nothing may be sent.
        $this->assertNull($user->aiConsent());
        $this->assertFalse($user->hasAiConsent());
    }
}
