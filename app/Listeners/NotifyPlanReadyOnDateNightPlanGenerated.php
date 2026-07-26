<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\DateNightPlanGenerated;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPlanReadyOnDateNightPlanGenerated implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(DateNightPlanGenerated $event): void
    {
        $this->notify($event->userOne, $event->plan);

        if ($event->userTwo !== null) {
            $this->notify($event->userTwo, $event->plan);
        }
    }

    private function notify(User $user, DateNightPlan $plan): void
    {
        AppNotification::create([
            'user_id' => $user->id,
            'type' => NotificationType::DateNightPlanReady,
            'title' => '❤️ Your Date Night plan is ready!',
            'body' => "Your personalised {$plan->theme_emoji} {$plan->theme} evening has been generated. Tap to view your plan.",
            'data' => ['plan_id' => $plan->id],
        ]);
    }
}
