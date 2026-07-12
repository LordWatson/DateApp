<?php

namespace App\Actions;

use App\Jobs\SendInvitationEmailJob;
use App\Models\PartnerInvitation;
use App\Models\User;
use App\Services\PartnerInvitationService;

class SendPartnerInvitationAction
{
    public function __construct(
        private readonly PartnerInvitationService $service,
    ) {}

    public function execute(User $sender, string $email): PartnerInvitation
    {
        $invitation = $this->service->createInvitation($sender, $email);

        SendInvitationEmailJob::dispatch($invitation);

        return $invitation;
    }
}
