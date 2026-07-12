<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogActivityOnAchievementUnlocked implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(AchievementUnlocked $event): void
    {
        $this->service->log($event->user, 'achievement_unlocked', $event->achievement, [
            'points' => $event->achievement->points,
        ]);
    }
}
