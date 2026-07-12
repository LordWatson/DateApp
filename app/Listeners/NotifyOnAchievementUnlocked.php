<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\AchievementUnlocked;
use App\Models\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyOnAchievementUnlocked implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(AchievementUnlocked $event): void
    {
        AppNotification::create([
            'user_id' => $event->user->id,
            'type' => NotificationType::PartnerCompleted,
            'title' => "🏆 Achievement unlocked: {$event->achievement->title}!",
            'body' => $event->achievement->description,
            'data' => ['achievement_id' => $event->achievement->id],
        ]);
    }
}
