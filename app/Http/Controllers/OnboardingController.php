<?php

namespace App\Http\Controllers;

use App\Actions\AcceptPartnerInvitationAction;
use App\Actions\SendPartnerInvitationAction;
use App\Enums\NotificationType;
use App\Http\Requests\Onboarding\CompleteProfileRequest;
use App\Http\Requests\Onboarding\SendInvitationRequest;
use App\Models\AppNotification;
use App\Models\PartnerInvitation;
use App\Models\User;
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
            'user' => $request->user()->only([
                'id', 'name', 'display_name', 'avatar', 'gender', 'date_of_birth', 'timezone', 'partner_id', 'onboarding_completed',
            ]),
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

        if ($refusal = $this->refuseInvite($request->user(), $invitation)) {
            return $refusal;
        }

        // Opening the link changes nothing: the person has to say yes.
        return Inertia::render('onboarding/AcceptInvite', [
            'token' => $token,
            'sender' => [
                'name' => $invitation->sender->display_name ?? $invitation->sender->name,
                'avatar' => $invitation->sender->avatar,
            ],
        ]);
    }

    public function confirmInvite(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = $this->invitationService->findValidInvitation($token);

        if (! $invitation) {
            return Inertia::render('onboarding/InvalidInvite');
        }

        if ($refusal = $this->refuseInvite($request->user(), $invitation)) {
            return $refusal;
        }

        $this->acceptInvitation->execute($invitation, $request->user());

        // Mark onboarding complete for recipient
        $request->user()->update(['onboarding_completed' => true]);

        return redirect()->route('onboarding.index');
    }

    public function declineInvite(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invitationService->findValidInvitation($token);

        if ($invitation && $invitation->sender_id !== $request->user()->id) {
            $this->invitationService->declineInvitation($invitation);

            AppNotification::create([
                'user_id' => $invitation->sender_id,
                'type' => NotificationType::InvitationDeclined,
                'title' => 'Your partner invitation was declined',
                'body' => "The invitation you sent to {$invitation->email} was declined. You can send a new one at any time.",
                'data' => [],
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Invitation declined.');
    }

    private function refuseInvite(User $user, PartnerInvitation $invitation): ?RedirectResponse
    {
        if ($user->partner_id) {
            return redirect()->route('dashboard')->with('error', 'You are already connected to a partner.');
        }

        if ($invitation->sender_id === $user->id) {
            return redirect()->route('onboarding.index')->with('error', 'You cannot accept your own invitation.');
        }

        return null;
    }

    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed' => true]);

        return redirect()->route('dashboard');
    }
}
