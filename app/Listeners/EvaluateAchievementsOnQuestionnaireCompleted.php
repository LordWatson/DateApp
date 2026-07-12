<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Jobs\AchievementEvaluationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class EvaluateAchievementsOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'analytics';

    public function handle(QuestionnaireCompleted $event): void
    {
        AchievementEvaluationJob::dispatch($event->user);
    }
}
