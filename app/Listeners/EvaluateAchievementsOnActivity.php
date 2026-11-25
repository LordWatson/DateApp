<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CalendarEventCreated;
use App\Events\ChallengeCompleted;
use App\Events\LoveNoteSent;
use App\Jobs\AchievementEvaluationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Re-checks achievements straight after an action that can unlock one, so a
 * first love note or first calendar event does not wait for the next questionnaire.
 */
final class EvaluateAchievementsOnActivity implements ShouldQueue
{
    public string $queue = 'analytics';

    public function handle(LoveNoteSent|CalendarEventCreated|ChallengeCompleted $event): void
    {
        AchievementEvaluationJob::dispatch($event instanceof LoveNoteSent ? $event->sender : $event->user);
    }
}
