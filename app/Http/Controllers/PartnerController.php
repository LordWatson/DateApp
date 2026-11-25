<?php

namespace App\Http\Controllers;

use App\Actions\ApplyConnectionChoicesAction;
use App\Actions\EndConnectionAction;
use App\Actions\SendPartnerInvitationAction;
use App\Enums\NotificationType;
use App\Http\Requests\Onboarding\CreateInviteLinkRequest;
use App\Http\Requests\Onboarding\SendInvitationRequest;
use App\Http\Requests\Partner\ConnectionChoicesRequest;
use App\Http\Requests\Partner\EndConnectionRequest;
use App\Jobs\SendInvitationEmailJob;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\PartnerInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function __construct(
        private readonly PartnerInvitationService $invitationService,
        private readonly SendPartnerInvitationAction $sendInvitation,
        private readonly ApplyConnectionChoicesAction $connectionChoices,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('partner', 'formerPartner');
        $pendingInvitation = $user->pendingInvitation();
        $formerPartner = $user->partner ? null : $user->formerPartner;

        return Inertia::render('Partner', [
            'partner' => $user->partner?->only(['id', 'name', 'display_name', 'avatar']),
            'pendingInvitation' => $pendingInvitation ? [
                'email' => $pendingInvitation->email,
                'expires_at' => $pendingInvitation->expires_at->toISOString(),
            ] : null,
            // The sender's own link, so they can share it without relying on the email.
            'inviteUrl' => $pendingInvitation ? route('onboarding.accept-invite', $pendingInvitation->token) : null,
            // When the two connected, not when the partner signed up.
            'connectedSince' => $user->partner ? $user->connected_at?->toISOString() : null,
            'paused' => $user->isConnectionPaused() ? [
                'at' => $user->connection_paused_at?->toISOString(),
                'by_me' => $user->connection_paused_by === $user->id,
            ] : null,
            // What ending the connection would ask about.
            'shared' => $user->partner ? $this->connectionChoices->summary($user, $user->partner) : null,
            'formerPartner' => $formerPartner ? ['name' => $formerPartner->display_name ?? $formerPartner->name] : null,
        ]);
    }

    public function sendInvite(SendInvitationRequest $request): RedirectResponse
    {
        $this->sendInvitation->execute($request->user(), $request->validated('email'));

        return back()->with('success', 'Invitation sent successfully!');
    }

    /**
     * An invitation with no email: the user passes the link on themselves.
     */
    public function createLink(CreateInviteLinkRequest $request): RedirectResponse
    {
        $this->invitationService->createInvitation($request->user());

        return back()->with('success', 'Your invitation link is ready to share.');
    }

    public function resendInvite(Request $request): RedirectResponse
    {
        $invitation = $request->user()->pendingInvitation();

        if (! $invitation) {
            return back()->with('error', 'There is no pending invitation to resend.');
        }

        if ($invitation->email === null) {
            return back()->with('error', 'This invitation is a link. Share the link again instead.');
        }

        SendInvitationEmailJob::dispatch($invitation);

        return back()->with('success', 'Invitation resent!');
    }

    public function revokeInvite(Request $request): RedirectResponse
    {
        $invitation = $request->user()->pendingInvitation();

        if (! $invitation) {
            return back()->with('error', 'There is no pending invitation to revoke.');
        }

        // Closing it is all a revoke is: the link stops working and nobody is told.
        $this->invitationService->declineInvitation($invitation);

        return back()->with('success', 'Invitation revoked.');
    }

    /**
     * "Take a break": stay connected and keep everything, but nothing new passes between the two.
     */
    public function pause(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->invitationService->pauseConnection($user)) {
            return back()->with('error', 'There is no active connection to pause.');
        }

        $this->tellPartner($user, NotificationType::ConnectionPaused, 'has paused your connection',
            'Nothing is lost. You both keep everything you have shared; notifications, love notes and shared planning stop until they resume.');

        return back()->with('success', 'Your connection is paused.');
    }

    public function resume(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->invitationService->resumeConnection($user)) {
            return back()->with('error', 'Only the person who paused the connection can resume it.');
        }

        $this->tellPartner($user->fresh(), NotificationType::ConnectionResumed, 'has resumed your connection', 'You are connected again as before.');

        return back()->with('success', 'Your connection is active again.');
    }

    public function disconnect(EndConnectionRequest $request, EndConnectionAction $endConnection): RedirectResponse
    {
        $endConnection->execute($request->user(), $request->boolean('keep_plans'), $request->boolean('keep_notes'));

        return redirect()->route('partner.index')->with('success', 'Partner disconnected.');
    }

    /**
     * After a connection has ended: what this person keeps of the shared history.
     */
    public function after(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $former = $user->partner_id === null ? $user->formerPartner : null;

        if (! $former) {
            return redirect()->route('partner.index');
        }

        return Inertia::render('PartnerAfter', [
            'formerPartner' => ['name' => $former->display_name ?? $former->name],
            'shared' => $this->connectionChoices->summary($user, $former),
        ]);
    }

    public function applyChoices(ConnectionChoicesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $former = $user->partner_id === null ? $user->formerPartner : null;

        if ($former) {
            $this->connectionChoices->execute($user, $former, $request->boolean('keep_plans'), $request->boolean('keep_notes'));
        }

        return redirect()->route('partner.index')->with('success', 'Your choices are saved.');
    }

    private function tellPartner(User $user, NotificationType $type, string $did, string $body): void
    {
        if (! $user->partner_id) {
            return;
        }

        AppNotification::create([
            'user_id' => $user->partner_id,
            'type' => $type,
            'title' => ($user->display_name ?? $user->name).' '.$did,
            'body' => $body,
            'data' => [],
        ]);
    }
}
