<?php

namespace App\Listeners;

use App\Events\MomentCreated;
use App\Jobs\SendMomentNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendMomentNotificationOnMomentCreated implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(MomentCreated $event): void
    {
        $partner = $event->user->partner;

        if ($partner === null) {
            return;
        }

        SendMomentNotificationJob::dispatch($partner, $event->moment);
    }
}
