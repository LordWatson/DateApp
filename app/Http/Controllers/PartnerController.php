<?php

namespace App\Http\Controllers;

use App\Actions\SendPartnerInvitationAction;
use App\Http\Requests\Onboarding\SendInvitationRequest;
use App\Mail\PartnerInvitationMail;
use App\Services\PartnerInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
            'partner' => $user->partner,
            'pendingInvitation' => $pendingInvitation ? [
                'email' => $pendingInvitation->email,
                'expires_at' => $pendingInvitation->expires_at->toISOString(),
                'token' => $pendingInvitation->token,
            ] : null,
            'connectedSince' => $user->partner
                ? $user->partner->created_at->toISOString()
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

        if ($invitation) {
            Mail::to($invitation->email)->queue(new PartnerInvitationMail($invitation));
        }

        return back()->with('success', 'Invitation resent!');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->invitationService->disconnectPartner($request->user());

        return redirect()->route('partner.index')->with('success', 'Partner disconnected.');
    }
}
