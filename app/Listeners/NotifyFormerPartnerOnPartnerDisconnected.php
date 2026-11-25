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
            'body' => 'You are no longer connected. Date nights you planned together and the love notes you exchanged stay in both your histories. '
                ."You each keep your own moments and calendar events, and can no longer see each other's.",
            'data' => [],
        ]);
    }
}
