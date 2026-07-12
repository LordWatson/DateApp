<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\PartnerConnected;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

final class CreatePartnerConnectedNotificationOnPartnerConnected implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PartnerConnected $event): void
    {
        $this->notify($event->userOne, $event->userTwo->display_name ?? $event->userTwo->name);
        $this->notify($event->userTwo, $event->userOne->display_name ?? $event->userOne->name);
    }

    private function notify(User $recipient, string $partnerName): void
    {
        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::PartnerCompleted,
            'title' => "💑 You are now connected with {$partnerName}!",
            'body' => 'Start your first questionnaire together.',
            'data' => [],
        ]);
    }
}
