<?php

namespace App\Actions\Fortify;

use App\Actions\AcceptPartnerInvitationAction;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\PartnerInvitationService;
use App\Services\RuntimeSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private readonly PartnerInvitationService $invitationService,
        private readonly AcceptPartnerInvitationAction $acceptInvitation,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (! app(RuntimeSettings::class)->flag('registration')) {
            throw ValidationException::withMessages([
                'email' => 'Registration is closed at the moment. Please try again later.',
            ]);
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please agree to the Terms and Privacy Policy to create an account.',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'terms_accepted_at' => now(),
        ]);

        $this->acceptPendingInvitation($user);

        return $user;
    }

    private function acceptPendingInvitation(User $user): void
    {
        $token = session()->pull('invitation_token');

        if (! $token) {
            return;
        }

        $invitation = $this->invitationService->findValidInvitation($token);

        if (! $invitation || $invitation->sender_id === $user->id) {
            return;
        }

        $this->acceptInvitation->execute($invitation, $user);

        $user->update(['onboarding_completed' => true]);
    }
}
