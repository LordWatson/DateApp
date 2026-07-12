<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Jobs\RefreshRelationshipStatisticsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class RefreshDashboardOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'analytics';

    public function handle(QuestionnaireCompleted $event): void
    {
        RefreshRelationshipStatisticsJob::dispatch($event->user);
    }
}
