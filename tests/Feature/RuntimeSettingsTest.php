<?php

namespace Tests\Feature;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\FeatureFlag;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\PartnerInvitationService;
use App\Services\RuntimeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Each test sets its rows before the first read: flags and settings are cached per request.
 */
class RuntimeSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function setFlag(string $key, bool $enabled): void
    {
        FeatureFlag::create(['key' => $key, 'label' => $key, 'enabled' => $enabled, 'group' => 'system']);
    }

    /**
     * @return object{calls: int}
     */
    private function spyOnAiProvider(): object
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $provider = new class implements AIProvider
        {
            public int $calls = 0;

            public function name(): string
            {
                return 'spy';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->calls++;

                return AIResponse::success($request->useCase, ['prompt' => 'Hi'], 'spy-model');
            }
        };

        $this->app->instance(AIProvider::class, $provider);

        return $provider;
    }

    // Defaults

    public function test_defaults_apply_when_no_rows_exist(): void
    {
        $settings = app(RuntimeSettings::class);

        $this->assertTrue($settings->flag('registration'));
        $this->assertFalse($settings->flag('maintenance_mode'));
        $this->assertTrue($settings->flag('ai_generation'));
        $this->assertFalse($settings->flag('unknown_flag'));
        $this->assertSame(7, $settings->invitationExpiryDays());
    }

    public function test_defaults_apply_when_the_tables_do_not_exist(): void
    {
        Schema::drop('feature_flags');
        Schema::drop('system_settings');

        $settings = app(RuntimeSettings::class);

        $this->assertTrue($settings->flag('registration'));
        $this->assertFalse($settings->flag('maintenance_mode'));
        $this->assertSame(7, $settings->invitationExpiryDays());
    }

    // Registration

    public function test_registration_closed_refuses_new_accounts_and_hides_sign_up(): void
    {
        $this->setFlag('registration', false);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('registrationOpen', false));

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ])->assertSessionHasErrors(['email' => 'Registration is closed at the moment. Please try again later.']);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_is_open_when_the_flag_is_on_or_missing(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('registrationOpen', true));

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    // Maintenance mode

    public function test_maintenance_mode_shows_the_maintenance_page_to_users_and_guests(): void
    {
        $this->setFlag('maintenance_mode', true);

        $this->get('/')
            ->assertStatus(503)
            ->assertInertia(fn (Assert $page) => $page->component('Maintenance'));

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertStatus(503)
            ->assertInertia(fn (Assert $page) => $page->component('Maintenance'));
    }

    public function test_maintenance_mode_answers_json_requests_with_503(): void
    {
        $this->setFlag('maintenance_mode', true);

        $this->actingAs(User::factory()->create())
            ->postJson('/love-notes/mark-read')
            ->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    public function test_maintenance_mode_lets_admins_in_and_keeps_login_reachable(): void
    {
        $this->setFlag('maintenance_mode', true);

        $this->get('/login')->assertOk();

        $admin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);

        $this->actingAs($admin)->get('/admin/feature-flags')->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_app_is_reachable_when_maintenance_mode_is_off(): void
    {
        $this->setFlag('maintenance_mode', false);

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
    }

    // AI generation

    public function test_ai_generation_off_makes_no_provider_call_and_returns_a_stable_failure(): void
    {
        $this->setFlag('ai_generation', false);
        $provider = $this->spyOnAiProvider();

        $response = app(AIService::class)->generateDateNightPlan(['mood' => 'cosy'], []);

        $this->assertFalse($response->successful);
        $this->assertSame('ai_disabled', $response->errorCode);
        $this->assertSame(0, $provider->calls);
        $this->assertDatabaseCount('ai_generations', 0);
    }

    public function test_ai_generation_calls_the_provider_when_the_flag_is_on_or_missing(): void
    {
        $provider = $this->spyOnAiProvider();

        $response = app(AIService::class)->generateDateNightPlan(['mood' => 'cosy'], []);

        $this->assertTrue($response->successful);
        $this->assertSame(1, $provider->calls);
    }

    // Invitation expiry

    public function test_invitation_expiry_follows_the_system_setting(): void
    {
        SystemSetting::create(['key' => 'invitation_expiry_days', 'label' => 'Expiry', 'value' => '3', 'type' => 'integer', 'group' => 'invitations']);
        $this->freezeTime();

        $invitation = app(PartnerInvitationService::class)->createInvitation(User::factory()->create(), 'partner@example.com');

        $this->assertSame(now()->addDays(3)->toDateTimeString(), $invitation->expires_at->toDateTimeString());
    }

    public function test_invitation_expiry_defaults_to_seven_days(): void
    {
        SystemSetting::create(['key' => 'invitation_expiry_days', 'label' => 'Expiry', 'value' => 'soon', 'type' => 'integer', 'group' => 'invitations']);
        $this->freezeTime();

        $invitation = app(PartnerInvitationService::class)->createInvitation(User::factory()->create(), 'partner@example.com');

        $this->assertSame(now()->addDays(7)->toDateTimeString(), $invitation->expires_at->toDateTimeString());
    }
}
