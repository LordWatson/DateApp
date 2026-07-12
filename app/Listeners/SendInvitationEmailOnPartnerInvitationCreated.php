<?php

namespace App\Listeners;

use App\Events\PartnerInvitationCreated;
use App\Jobs\SendInvitationEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendInvitationEmailOnPartnerInvitationCreated implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PartnerInvitationCreated $event): void
    {
        SendInvitationEmailJob::dispatch($event->invitation);
    }
}
