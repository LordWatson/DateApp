<?php

namespace App\Listeners;

use App\Events\PartnerConnected;
use App\Jobs\RefreshRelationshipStatisticsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class RefreshDashboardOnPartnerConnected implements ShouldQueue
{
    public string $queue = 'analytics';

    public function handle(PartnerConnected $event): void
    {
        RefreshRelationshipStatisticsJob::dispatch($event->userOne);
        RefreshRelationshipStatisticsJob::dispatch($event->userTwo);
    }
}
