<?php

namespace App\Services;

use App\Models\PartnerInvitation;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

class PartnerInvitationService
{
    /**
     * @param  string|null  $email  Null for an invitation the sender shares as a link themselves.
     */
    public function createInvitation(User $sender, ?string $email = null): PartnerInvitation
    {
        // Revoke any existing pending invitations from this sender
        $sender->sentInvitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        return PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(app(RuntimeSettings::class)->invitationExpiryDays()),
        ]);
    }

    /**
     * The link for the user's own pending invitation, for them to pass on. Never anyone else's.
     */
    public function shareableUrl(User $sender): ?string
    {
        $invitation = $sender->pendingInvitation();

        return $invitation ? route('onboarding.accept-invite', $invitation->token) : null;
    }

    public function findValidInvitation(string $token): ?PartnerInvitation
    {
        return PartnerInvitation::with('sender')
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            // An invitation dies once its sender connects with someone else.
            ->whereHas('sender', fn ($query) => $query->whereNull('partner_id'))
            ->first();
    }

    public function acceptInvitation(PartnerInvitation $invitation, User $recipient): void
    {
        $sender = $invitation->sender;

        // Connecting someone who already has a partner would leave the old partner pointing at them.
        if ($sender->fresh()->partner_id !== null || $recipient->fresh()->partner_id !== null) {
            throw new DomainException('One of you is already connected to a partner.');
        }

        // Atomically connect both users
        \DB::transaction(function () use ($invitation, $sender, $recipient): void {
            $invitation->update(['accepted_at' => now()]);

            $sender->update(['partner_id' => $recipient->id, 'connected_at' => now()]);
            $recipient->update(['partner_id' => $sender->id, 'connected_at' => now()]);
        });
    }

    /**
     * Close the invitation without connecting anyone.
     */
    public function declineInvitation(PartnerInvitation $invitation): void
    {
        $invitation->update(['expires_at' => now()]);
    }

    /**
     * Pause the connection for both people. Either partner can do it.
     */
    public function pauseConnection(User $by): bool
    {
        $partner = $by->partner;

        if (! $partner || $by->isConnectionPaused()) {
            return false;
        }

        \DB::transaction(function () use ($by, $partner): void {
            $paused = ['connection_paused_at' => now(), 'connection_paused_by' => $by->id];

            $by->update($paused);
            $partner->update($paused);
        });

        return true;
    }

    /**
     * Only the person who paused can resume: the other should not be pulled back in.
     */
    public function resumeConnection(User $by): bool
    {
        if (! $by->isConnectionPaused() || $by->connection_paused_by !== $by->id) {
            return false;
        }

        \DB::transaction(function () use ($by): void {
            foreach (array_filter([$by, $by->partner]) as $person) {
                $resumed = ['connection_paused_at' => null, 'connection_paused_by' => null];

                // A streak that was alive when the pause began carries on from today.
                if ($person->currentChallengeStreak() > 0) {
                    $resumed['challenge_streak_date'] = now()->subDay()->toDateString();
                }

                $person->update($resumed);
            }
        });

        return true;
    }

    public function disconnectPartner(User $user): void
    {
        $partner = $user->partner;

        \DB::transaction(function () use ($user, $partner): void {
            // The streak belonged to the couple; each person's best stays.
            $ended = [
                'partner_id' => null,
                'connected_at' => null,
                'connection_paused_at' => null,
                'connection_paused_by' => null,
                'challenge_streak' => 0,
                'challenge_streak_date' => null,
            ];

            // Each remembers the other, so both can still choose what to keep.
            $user->update($partner ? [...$ended, 'former_partner_id' => $partner->id] : $ended);

            if ($partner) {
                $partner->update([...$ended, 'former_partner_id' => $user->id]);
            }
        });
    }
}
