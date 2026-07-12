<?php

namespace App\Listeners;

use App\Events\DateNightPlanGenerated;
use App\Jobs\SendSummaryEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPlanEmailsOnDateNightPlanGenerated implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(DateNightPlanGenerated $event): void
    {
        SendSummaryEmailJob::dispatch($event->userOne, $event->plan);
        SendSummaryEmailJob::dispatch($event->userTwo, $event->plan);
    }
}
