<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\PartnerDisconnected;
use App\Models\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyFormerPartnerOnPartnerDisconnected implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PartnerDisconnected $event): void
    {
        $name = $event->user->display_name ?? $event->user->name;

        AppNotification::create([
            'user_id' => $event->formerPartner->id,
            'type' => NotificationType::PartnerLeft,
            'title' => "{$name} has disconnected from you",
            'body' => 'You are no longer connected. The date nights you planned together and your love notes are still in your account: '
                .'open this to choose whether to keep them. '
                ."You each keep your own moments and calendar events, and can no longer see each other's.",
            'data' => [],
        ]);
    }
}
