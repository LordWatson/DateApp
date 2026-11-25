<?php

namespace App\Http\Controllers;

use App\Actions\SendPartnerInvitationAction;
use App\Events\PartnerDisconnected;
use App\Http\Requests\Onboarding\SendInvitationRequest;
use App\Jobs\SendInvitationEmailJob;
use App\Models\PartnerInvitation;
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
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('partner');
        $pendingInvitation = $user->pendingInvitation();

        return Inertia::render('Partner', [
            'partner' => $user->partner?->only(['id', 'name', 'display_name', 'avatar']),
            'pendingInvitation' => $pendingInvitation ? [
                'email' => $pendingInvitation->email,
                'expires_at' => $pendingInvitation->expires_at->toISOString(),
            ] : null,
            // When the two connected, not when the partner signed up.
            'connectedSince' => $user->partner
                ? PartnerInvitation::query()
                    ->whereNotNull('accepted_at')
                    ->whereIn('sender_id', [$user->id, $user->partner->id])
                    ->latest('accepted_at')
                    ->value('accepted_at')?->toISOString()
                : null,
        ]);
    }

    public function sendInvite(SendInvitationRequest $request): RedirectResponse
    {
        $this->sendInvitation->execute($request->user(), $request->validated('email'));

        return back()->with('success', 'Invitation sent successfully!');
    }

    public function resendInvite(Request $request): RedirectResponse
    {
        $invitation = $request->user()->pendingInvitation();

        if (! $invitation) {
            return back()->with('error', 'There is no pending invitation to resend.');
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

    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        $this->invitationService->disconnectPartner($user);

        if ($partner) {
            PartnerDisconnected::dispatch($user->fresh(), $partner->fresh());
        }

        return redirect()->route('partner.index')->with('success', 'Partner disconnected.');
    }
}
