<?php

namespace App\Listeners;

use App\Events\LoveNoteSent;
use App\Jobs\SendLoveNoteNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendLoveNoteNotificationOnLoveNoteSent implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(LoveNoteSent $event): void
    {
        SendLoveNoteNotificationJob::dispatch($event->recipient, $event->loveNote);
    }
}
