<?php

namespace App\Services;

use App\Models\PartnerInvitation;
use App\Models\User;
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
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function findValidInvitation(string $token): ?PartnerInvitation
    {
        return PartnerInvitation::with('sender')
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function acceptInvitation(PartnerInvitation $invitation, User $recipient): void
    {
        $sender = $invitation->sender;

        // Atomically connect both users
        \DB::transaction(function () use ($invitation, $sender, $recipient): void {
            $invitation->update(['accepted_at' => now()]);

            $sender->update(['partner_id' => $recipient->id]);
            $recipient->update(['partner_id' => $sender->id]);
        });
    }

    public function disconnectPartner(User $user): void
    {
        $partner = $user->partner;

        \DB::transaction(function () use ($user, $partner): void {
            $user->update(['partner_id' => null]);

            if ($partner) {
                $partner->update(['partner_id' => null]);
            }
        });
    }
}
