<?php

namespace App\Actions;

use App\Mail\PartnerInvitationMail;
use App\Models\PartnerInvitation;
use App\Models\User;
use App\Services\PartnerInvitationService;
use Illuminate\Support\Facades\Mail;

class SendPartnerInvitationAction
{
    public function __construct(
        private readonly PartnerInvitationService $service,
    ) {}

    public function execute(User $sender, string $email): PartnerInvitation
    {
        $invitation = $this->service->createInvitation($sender, $email);

        Mail::to($email)->queue(new PartnerInvitationMail($invitation));

        return $invitation;
    }
}
