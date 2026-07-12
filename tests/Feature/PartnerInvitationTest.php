<?php

namespace Tests\Feature;

use App\Models\PartnerInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PartnerInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_partner_invitation(): void
    {
        Mail::fake();

        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.send-invite'), [
            'email' => 'partner@example.com',
        ]);

        $response->assertRedirect(route('onboarding.index'));
        $this->assertDatabaseHas('partner_invitations', [
            'sender_id' => $user->id,
            'email' => 'partner@example.com',
        ]);
    }

    public function test_user_cannot_invite_themselves(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.send-invite'), [
            'email' => 'me@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_invitation_token_is_unique_and_secure(): void
    {
        Mail::fake();

        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $this->post(route('onboarding.send-invite'), ['email' => 'partner@example.com']);

        $invitation = PartnerInvitation::where('sender_id', $user->id)->first();
        $this->assertNotNull($invitation->token);
        $this->assertEquals(64, strlen($invitation->token));
    }

    public function test_invitation_expires_after_seven_days(): void
    {
        Mail::fake();

        $user = User::factory()->create(['onboarding_completed' => false]);
        $this->actingAs($user);

        $this->post(route('onboarding.send-invite'), ['email' => 'partner@example.com']);

        $invitation = PartnerInvitation::where('sender_id', $user->id)->first();
        $this->assertTrue($invitation->expires_at->isFuture());
        $this->assertTrue($invitation->expires_at->diffInDays(now()) <= 7);
    }

    public function test_valid_invitation_can_be_accepted(): void
    {
        Mail::fake();

        $sender = User::factory()->create(['onboarding_completed' => true]);
        $recipient = User::factory()->create(['onboarding_completed' => false]);

        $invitation = PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => $recipient->email,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($recipient);

        $response = $this->get(route('onboarding.accept-invite', ['token' => $invitation->token]));

        $response->assertRedirect(route('onboarding.index'));
        $this->assertEquals($recipient->id, $sender->fresh()->partner_id);
        $this->assertEquals($sender->id, $recipient->fresh()->partner_id);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_invitation_shows_invalid_page(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['onboarding_completed' => false]);

        $invitation = PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => $recipient->email,
            'token' => str_repeat('b', 64),
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($recipient);

        $response = $this->get(route('onboarding.accept-invite', ['token' => $invitation->token]));

        $response->assertInertia(fn ($page) => $page->component('onboarding/InvalidInvite'));
    }

    public function test_already_accepted_invitation_cannot_be_reused(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create(['onboarding_completed' => false]);
        $thirdUser = User::factory()->create(['onboarding_completed' => false]);

        $invitation = PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => $recipient->email,
            'token' => str_repeat('c', 64),
            'expires_at' => now()->addDays(7),
            'accepted_at' => now(),
        ]);

        $this->actingAs($thirdUser);

        $response = $this->get(route('onboarding.accept-invite', ['token' => $invitation->token]));

        $response->assertInertia(fn ($page) => $page->component('onboarding/InvalidInvite'));
    }

    public function test_user_cannot_accept_own_invitation(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);

        $invitation = PartnerInvitation::create([
            'sender_id' => $user->id,
            'email' => 'someone@example.com',
            'token' => str_repeat('d', 64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('onboarding.accept-invite', ['token' => $invitation->token]));

        $response->assertRedirect(route('onboarding.index'));
        $this->assertNull($user->fresh()->partner_id);
    }

    public function test_partner_can_be_disconnected(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $user = User::factory()->create([
            'onboarding_completed' => true,
            'partner_id' => $partner->id,
        ]);
        $partner->update(['partner_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete(route('partner.disconnect'));

        $response->assertRedirect(route('partner.index'));
        $this->assertNull($user->fresh()->partner_id);
        $this->assertNull($partner->fresh()->partner_id);
    }

    public function test_unauthenticated_user_visiting_invite_link_is_redirected_to_register(): void
    {
        $sender = User::factory()->create();

        $invitation = PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => 'new@example.com',
            'token' => str_repeat('e', 64),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get(route('onboarding.accept-invite', ['token' => $invitation->token]));

        $response->assertRedirect(route('register', ['invitation_token' => $invitation->token]));
    }
}
