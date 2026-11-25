<?php

namespace Tests\Feature;

use App\Jobs\SendInvitationEmailJob;
use App\Mail\PartnerInvitationMail;
use App\Models\PartnerInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InviteLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::factory()->create(['display_name' => 'Alex']);
    }

    public function test_a_link_invitation_needs_no_email_and_sends_none(): void
    {
        Mail::fake();

        $this->actingAs($this->sender)
            ->post(route('partner.create-link'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $invitation = PartnerInvitation::sole();
        $this->assertSame($this->sender->id, $invitation->sender_id);
        $this->assertNull($invitation->email);
        $this->assertTrue($invitation->expires_at->isFuture());
        Mail::assertNothingOutgoing();
    }

    public function test_only_the_sender_is_given_their_link(): void
    {
        $this->actingAs($this->sender)->post(route('partner.create-link'));
        $token = PartnerInvitation::sole()->token;

        $this->actingAs($this->sender)
            ->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page
                ->where('inviteUrl', route('onboarding.accept-invite', $token))
                ->where('pendingInvitation.email', null)
            );

        $this->actingAs(User::factory()->create())
            ->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page->where('inviteUrl', null));
    }

    public function test_the_other_person_confirms_before_anything_connects(): void
    {
        $this->actingAs($this->sender)->post(route('partner.create-link'));
        $token = PartnerInvitation::sole()->token;
        $recipient = User::factory()->create();

        $this->actingAs($recipient)
            ->get(route('onboarding.accept-invite', $token))
            ->assertInertia(fn ($page) => $page->component('onboarding/AcceptInvite')->where('sender.name', 'Alex'));

        $this->assertNull($recipient->fresh()->partner_id);

        $this->actingAs($recipient)->post(route('onboarding.confirm-invite', $token))->assertRedirect();

        $this->assertSame($this->sender->id, $recipient->fresh()->partner_id);
        $this->assertSame($recipient->id, $this->sender->fresh()->partner_id);
    }

    public function test_someone_with_a_partner_cannot_create_a_link(): void
    {
        $partner = User::factory()->create(['partner_id' => $this->sender->id]);
        $this->sender->update(['partner_id' => $partner->id]);

        $this->actingAs($this->sender)
            ->post(route('partner.create-link'))
            ->assertSessionHasErrors('link');

        $this->assertDatabaseCount('partner_invitations', 0);
    }

    public function test_creating_a_link_replaces_a_pending_email_invitation(): void
    {
        $this->actingAs($this->sender)->post(route('partner.send-invite'), ['email' => 'sam@example.com']);
        $this->actingAs($this->sender)->post(route('partner.create-link'));

        $emailed = PartnerInvitation::where('email', 'sam@example.com')->sole();
        $this->assertTrue($emailed->expires_at->isPast() || $emailed->expires_at->equalTo(now()));
        $this->assertNull($this->sender->pendingInvitation()->email);
    }

    public function test_a_link_invitation_cannot_be_resent_by_email(): void
    {
        Mail::fake();
        $this->actingAs($this->sender)->post(route('partner.create-link'));

        $this->actingAs($this->sender)
            ->post(route('partner.resend-invite'))
            ->assertSessionHas('error');

        // Even if the job is queued for one, there is no address to send to.
        SendInvitationEmailJob::dispatchSync(PartnerInvitation::sole());

        Mail::assertNotSent(PartnerInvitationMail::class);
    }

    public function test_an_expired_link_invitation_is_announced_without_an_address(): void
    {
        $this->actingAs($this->sender)->post(route('partner.create-link'));

        $this->travel(8)->days();
        $this->artisan('datenight:expire-invitations')->assertSuccessful();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->sender->id,
            'type' => 'invitation_expired',
            'body' => 'Your invitation link was not accepted in time. You can send a new one from the Partner page.',
        ]);
    }

    public function test_a_link_can_be_created_during_onboarding(): void
    {
        $newcomer = User::factory()->create(['onboarding_completed' => false]);

        $this->actingAs($newcomer)
            ->post(route('onboarding.create-link'))
            ->assertRedirect(route('onboarding.index'));

        $token = PartnerInvitation::where('sender_id', $newcomer->id)->sole()->token;

        $this->actingAs($newcomer)
            ->get(route('onboarding.index'))
            ->assertInertia(fn ($page) => $page->where('inviteUrl', route('onboarding.accept-invite', $token)));
    }

    public function test_creating_links_is_rate_limited(): void
    {
        foreach (['partner.create-link', 'onboarding.create-link'] as $name) {
            $this->assertContains('throttle:invites', app('router')->getRoutes()->getByName($name)->gatherMiddleware());
        }
    }
}
