<?php

namespace App\Actions;

use App\Events\LoveNoteSent;
use App\Models\LoveNote;
use App\Models\User;

class SendLoveNoteAction
{
    public function execute(User $sender, User $recipient, string $message): LoveNote
    {
        $note = LoveNote::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'message' => $message,
        ]);

        LoveNoteSent::dispatch($sender, $recipient, $note);

        return $note;
    }
}
