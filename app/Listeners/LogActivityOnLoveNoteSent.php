<?php

namespace App\Listeners;

use App\Events\LoveNoteSent;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogActivityOnLoveNoteSent implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(LoveNoteSent $event): void
    {
        $this->service->log($event->sender, 'love_note_sent', $event->loveNote, [
            'recipient_id' => $event->recipient->id,
        ]);
    }
}
