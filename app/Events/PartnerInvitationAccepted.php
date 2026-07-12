<?php

namespace App\Events;

use App\Models\PartnerInvitation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PartnerInvitationAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PartnerInvitation $invitation,
        public readonly User $recipient,
    ) {}
}
