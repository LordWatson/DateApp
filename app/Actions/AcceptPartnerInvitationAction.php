<?php

namespace App\Actions;

use App\Mail\PartnerConnectedMail;
use App\Models\PartnerInvitation;
use App\Models\User;
use App\Services\PartnerInvitationService;
use Illuminate\Support\Facades\Mail;

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

        Mail::to($sender->email)->queue(new PartnerConnectedMail($sender, $recipient));
        Mail::to($recipient->email)->queue(new PartnerConnectedMail($recipient, $sender));
    }
}
