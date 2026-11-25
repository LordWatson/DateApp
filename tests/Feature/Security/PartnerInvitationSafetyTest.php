<?php

namespace Tests\Feature\Security;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\PartnerInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PartnerInvitationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function invitationFrom(User $sender, string $email = 'someone@example.com'): PartnerInvitation
    {
        return PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => $email,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function couple(): array
    {
        $one = User::factory()->create();
        $two = User::factory()->create(['partner_id' => $one->id]);
        $one->update(['partner_id' => $two->id]);

        return [$one, $two];
    }

    public function test_opening_an_invitation_link_connects_nobody(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $invitation = $this->invitationFrom($sender);

        $this->actingAs($recipient)
            ->get(route('onboarding.accept-invite', $invitation->token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('onboarding/AcceptInvite'));

        $this->assertNull($sender->fresh()->partner_id);
        $this->assertNull($recipient->fresh()->partner_id);
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_declining_closes_the_invitation_and_tells_the_sender(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $invitation = $this->invitationFrom($sender);

        $this->actingAs($recipient)
            ->post(route('onboarding.decline-invite', $invitation->token))
            ->assertRedirect(route('dashboard'));

        $this->assertNull($sender->fresh()->partner_id);
        $this->assertTrue($invitation->fresh()->expires_at->isPast());
        $this->assertSame(1, AppNotification::where('user_id', $sender->id)->where('type', NotificationType::InvitationDeclined)->count());

        // A declined link cannot be used afterwards.
        $this->actingAs($recipient)->post(route('onboarding.confirm-invite', $invitation->token));
        $this->assertNull($sender->fresh()->partner_id);
    }

    public function test_someone_with_a_partner_cannot_send_an_invitation(): void
    {
        [$one, $two] = $this->couple();

        $this->actingAs($one)
            ->post(route('partner.send-invite'), ['email' => 'third@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, PartnerInvitation::count());
        $this->assertSame($two->id, $one->fresh()->partner_id);
    }

    public function test_an_old_invitation_cannot_break_up_an_existing_couple(): void
    {
        $sender = User::factory()->create();
        $invitation = $this->invitationFrom($sender);

        // The sender connects with someone else before the invitation is used.
        $partner = User::factory()->create(['partner_id' => $sender->id]);
        $sender->update(['partner_id' => $partner->id]);

        $latecomer = User::factory()->create();

        $this->actingAs($latecomer)->post(route('onboarding.confirm-invite', $invitation->token));

        $this->assertSame($partner->id, $sender->fresh()->partner_id);
        $this->assertSame($sender->id, $partner->fresh()->partner_id);
        $this->assertNull($latecomer->fresh()->partner_id);
    }

    public function test_invitations_are_rate_limited_per_user(): void
    {
        $sender = User::factory()->create();

        foreach (range(1, 10) as $attempt) {
            $this->actingAs($sender)
                ->post(route('partner.send-invite'), ['email' => "person{$attempt}@example.com"])
                ->assertRedirect();
        }

        $this->actingAs($sender)
            ->post(route('partner.send-invite'), ['email' => 'one-too-many@example.com'])
            ->assertStatus(429);
    }

    public function test_partner_and_shared_user_props_carry_no_private_fields(): void
    {
        [$one, $two] = $this->couple();
        $two->update(['date_of_birth' => '1990-01-01']);

        $this->actingAs($one)->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page
                ->where('partner.id', $two->id)
                ->missing('partner.email')
                ->missing('partner.date_of_birth')
                ->missing('partner.role_id')
                ->missing('auth.user.role_id')
                ->missing('auth.user.date_of_birth')
                ->missing('auth.user.is_suspended')
                ->where('auth.can', null));

        $this->actingAs($one)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->missing('partner.email')->missing('partner.date_of_birth'));
    }
}
