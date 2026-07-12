<?php

namespace App\Listeners;

use App\Events\PartnerConnected;
use App\Jobs\SendPartnerConnectedEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPartnerConnectedEmailsOnPartnerConnected implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PartnerConnected $event): void
    {
        SendPartnerConnectedEmailJob::dispatch($event->userOne, $event->userTwo);
        SendPartnerConnectedEmailJob::dispatch($event->userTwo, $event->userOne);
    }
}
