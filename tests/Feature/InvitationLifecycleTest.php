<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\PartnerInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function invitationFrom(User $sender): PartnerInvitation
    {
        return PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => 'someone@example.com',
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_logging_in_after_opening_an_invitation_lands_on_the_confirmation_page(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $invitation = $this->invitationFrom($sender);
        $inviteUrl = route('onboarding.accept-invite', ['token' => $invitation->token]);

        $this->get($inviteUrl)->assertRedirect(route('register'));

        $this->post(route('login.store'), ['email' => $recipient->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))->assertRedirect($inviteUrl);

        $this->get($inviteUrl)->assertInertia(fn ($page) => $page->component('onboarding/AcceptInvite'));

        // Asked once: nothing is connected yet, and the dashboard is reachable afterwards.
        $this->assertNull($recipient->fresh()->partner_id);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_sender_can_revoke_a_pending_invitation(): void
    {
        $sender = User::factory()->create();
        $invitation = $this->invitationFrom($sender);

        $this->actingAs($sender)->delete(route('partner.revoke-invite'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($sender->pendingInvitation());

        $this->actingAs(User::factory()->create())
            ->get(route('onboarding.accept-invite', ['token' => $invitation->token]))
            ->assertInertia(fn ($page) => $page->component('onboarding/InvalidInvite'));
    }

    public function test_revoking_with_nothing_pending_reports_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('partner.revoke-invite'))
            ->assertSessionHas('error');
    }

    public function test_sender_is_told_once_that_an_invitation_expired(): void
    {
        $sender = User::factory()->create();
        $this->invitationFrom($sender);

        $this->artisan('datenight:expire-invitations')->assertSuccessful();
        $this->assertSame(0, AppNotification::count());

        $this->travel(8)->days();

        $this->artisan('datenight:expire-invitations')->assertSuccessful();
        $this->artisan('datenight:expire-invitations')->assertSuccessful();

        $notification = AppNotification::sole();
        $this->assertSame($sender->id, $notification->user_id);
        $this->assertSame(NotificationType::InvitationExpired, $notification->type);
    }

    public function test_a_revoked_invitation_is_not_reported_as_expired(): void
    {
        $sender = User::factory()->create();
        $this->invitationFrom($sender);

        $this->actingAs($sender)->delete(route('partner.revoke-invite'));
        $this->travel(1)->days();

        $this->artisan('datenight:expire-invitations')->assertSuccessful();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_the_other_partner_is_notified_of_a_disconnect(): void
    {
        $user = User::factory()->create(['display_name' => 'Alex']);
        $partner = User::factory()->create(['partner_id' => $user->id]);
        $user->update(['partner_id' => $partner->id]);

        $this->actingAs($user)->delete(route('partner.disconnect'))->assertRedirect(route('partner.index'));

        $notification = AppNotification::sole();
        $this->assertSame($partner->id, $notification->user_id);
        $this->assertSame(NotificationType::PartnerLeft, $notification->type);
        $this->assertStringContainsString('Alex', $notification->title);
    }
}
