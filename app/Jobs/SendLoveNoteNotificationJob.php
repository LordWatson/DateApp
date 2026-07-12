<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        AppNotification::create([
            'user_id' => $this->recipient->id,
            'type' => NotificationType::LoveNoteReceived,
            'title' => '💌 You received a love note!',
            'body' => $this->loveNote->message,
            'data' => ['love_note_id' => $this->loveNote->id],
        ]);
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
