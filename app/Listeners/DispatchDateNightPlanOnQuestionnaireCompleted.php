<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Jobs\GenerateDateNightPlanJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class DispatchDateNightPlanOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(QuestionnaireCompleted $event): void
    {
        $partner = $event->user->partner;

        if (! $partner) {
            return;
        }

        GenerateDateNightPlanJob::dispatch($event->user, $partner, $event->questionnaire);
    }
}
