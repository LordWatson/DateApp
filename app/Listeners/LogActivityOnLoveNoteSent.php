<?php

namespace App\Listeners;

use App\Events\LoveNoteSent;
use App\Services\ActivityLogService;

final class LogActivityOnLoveNoteSent
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(LoveNoteSent $event): void
    {
        $this->service->log($event->sender, 'love_note_sent', $event->loveNote, [
            'recipient_id' => $event->recipient->id,
        ]);
    }
}
