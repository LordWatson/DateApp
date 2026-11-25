<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\ChallengeCompleted;
use App\Models\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPartnerOnChallengeCompleted implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(ChallengeCompleted $event): void
    {
        $user = $event->user;

        if (! $user->partner_id || $user->isConnectionPaused()) {
            return;
        }

        AppNotification::create([
            'user_id' => $user->partner_id,
            'type' => NotificationType::DailyChallenge,
            'title' => ($user->display_name ?? $user->name)." did today's challenge",
            'body' => $event->challenge->title,
            'data' => ['challenge_id' => $event->challenge->id],
        ]);
    }
}
