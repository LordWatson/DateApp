<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Mail\LoveNoteReceivedMail;
use App\Models\AppNotification;
use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendLoveNoteNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly User $recipient,
        public readonly LoveNote $loveNote,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $sender = $this->loveNote->sender;

        AppNotification::create([
            'user_id' => $this->recipient->id,
            'type' => NotificationType::LoveNoteReceived,
            'title' => '💌 You received a love note!',
            'body' => ($sender->display_name ?? $sender->name).' sent you a love note. Tap to read it.',
            'data' => ['love_note_id' => $this->loveNote->id],
        ]);

        Mail::to($this->recipient->email)
            ->queue(new LoveNoteReceivedMail($this->recipient, $sender));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendLoveNoteNotificationJob failed', [
            'recipient' => $this->recipient->id,
            'love_note' => $this->loveNote->id,
            'error' => $e->getMessage(),
        ]);
    }
}
