<?php

namespace App\Actions;

use App\Events\PartnerConnected;
use App\Events\PartnerInvitationAccepted;
use App\Models\PartnerInvitation;
use App\Models\User;
use App\Services\PartnerInvitationService;

class AcceptPartnerInvitationAction
{
    public function __construct(
        private readonly PartnerInvitationService $service,
    ) {}

    public function execute(PartnerInvitation $invitation, User $recipient): void
    {
        $this->service->acceptInvitation($invitation, $recipient);

        $sender = $invitation->sender->fresh();
        $recipient = $recipient->fresh();

        PartnerInvitationAccepted::dispatch($invitation, $recipient);
        PartnerConnected::dispatch($sender, $recipient);
    }
}
