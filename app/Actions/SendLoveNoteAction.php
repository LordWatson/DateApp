<?php

namespace App\Actions;

use App\Models\LoveNote;
use App\Models\User;
use App\Services\NotificationService;

class SendLoveNoteAction
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(User $sender, User $recipient, string $message): LoveNote
    {
        $note = LoveNote::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'message' => $message,
        ]);

        $this->notificationService->notifyLoveNoteReceived($recipient, $note);

        return $note;
    }
}
