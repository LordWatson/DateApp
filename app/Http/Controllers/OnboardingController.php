<?php

namespace App\Http\Controllers;

use App\Actions\AcceptPartnerInvitationAction;
use App\Actions\SendPartnerInvitationAction;
use App\Http\Requests\Onboarding\CompleteProfileRequest;
use App\Http\Requests\Onboarding\SendInvitationRequest;
use App\Services\PartnerInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly PartnerInvitationService $invitationService,
        private readonly SendPartnerInvitationAction $sendInvitation,
        private readonly AcceptPartnerInvitationAction $acceptInvitation,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->user()->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/Index', [
            'user' => $request->user(),
        ]);
    }

    public function completeProfile(CompleteProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('onboarding.index');
    }

    public function sendInvite(SendInvitationRequest $request): RedirectResponse
    {
        $this->sendInvitation->execute($request->user(), $request->validated('email'));

        return redirect()->route('onboarding.index');
    }

    public function acceptInvite(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = $this->invitationService->findValidInvitation($token);

        if (! $invitation) {
            return Inertia::render('onboarding/InvalidInvite');
        }

        // If not logged in, store token in session and redirect to register
        if (! $request->user()) {
            $request->session()->put('invitation_token', $token);

            return redirect()->route('register');
        }

        // If already has a partner, redirect to dashboard
        if ($request->user()->partner_id) {
            return redirect()->route('dashboard')->with('error', 'You are already connected to a partner.');
        }

        // Cannot accept own invitation
        if ($invitation->sender_id === $request->user()->id) {
            return redirect()->route('onboarding.index')->with('error', 'You cannot accept your own invitation.');
        }

        $this->acceptInvitation->execute($invitation, $request->user());

        // Mark onboarding complete for recipient
        $request->user()->update(['onboarding_completed' => true]);

        return redirect()->route('onboarding.index');
    }

    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed' => true]);

        return redirect()->route('dashboard');
    }
}
