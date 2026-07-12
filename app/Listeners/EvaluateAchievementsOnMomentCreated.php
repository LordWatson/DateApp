<?php

namespace App\Listeners;

use App\Events\MomentCreated;
use App\Jobs\AchievementEvaluationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class EvaluateAchievementsOnMomentCreated implements ShouldQueue
{
    public string $queue = 'analytics';

    public function handle(MomentCreated $event): void
    {
        AchievementEvaluationJob::dispatch($event->user);
    }
}
