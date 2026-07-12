<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

final class InvalidateCacheOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(QuestionnaireCompleted $event): void
    {
        Cache::forget("dashboard:{$event->user->id}");
        Cache::forget("compatibility:{$event->user->id}");

        if ($event->user->partner_id) {
            Cache::forget("dashboard:{$event->user->partner_id}");
            Cache::forget("compatibility:{$event->user->partner_id}");
        }
    }
}
