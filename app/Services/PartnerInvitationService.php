<?php

namespace App\Services;

use App\Models\PartnerInvitation;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

class PartnerInvitationService
{
    public function createInvitation(User $sender, string $email): PartnerInvitation
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

    public function disconnectPartner(User $user): void
    {
        $partner = $user->partner;

        \DB::transaction(function () use ($user, $partner): void {
            $user->update(['partner_id' => null, 'connected_at' => null]);

            if ($partner) {
                $partner->update(['partner_id' => null, 'connected_at' => null]);
            }
        });
    }
}
