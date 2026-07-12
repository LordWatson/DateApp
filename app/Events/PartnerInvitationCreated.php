<?php

namespace App\Events;

use App\Models\PartnerInvitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PartnerInvitationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PartnerInvitation $invitation,
    ) {}
}
