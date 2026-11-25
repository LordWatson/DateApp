<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\User;

class NotificationService
{
    public function notifyPartnerViewedPlan(User $recipient, DateNightPlan $plan): void
    {
        $alreadyTold = AppNotification::where('user_id', $recipient->id)
            ->where('type', NotificationType::PartnerViewedPlan)
            ->where('data->plan_id', $plan->id)
            ->exists();

        if ($alreadyTold) {
            return;
        }

        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::PartnerViewedPlan,
            'title' => '👀 Your partner viewed the Date Night plan!',
            'body' => "They've seen your {$plan->theme_emoji} {$plan->theme} plan. Tonight is on!",
            'data' => ['plan_id' => $plan->id],
        ]);
    }

    public function notifyPartnerLikedPlan(User $recipient, User $sender, DateNightPlan $plan): void
    {
        $senderName = $sender->display_name ?? $sender->name;

        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::PartnerLikedPlan,
            'title' => "❤️ {$senderName} loves your Date Night plan!",
            'body' => "They liked your {$plan->theme_emoji} {$plan->theme} plan. Looks like tonight's a yes!",
            'data' => ['plan_id' => $plan->id],
        ]);
    }

    public function notifyPartnerCompletedPlan(User $recipient, User $sender, DateNightPlan $plan): void
    {
        $senderName = $sender->display_name ?? $sender->name;

        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::PartnerCompletedPlan,
            'title' => "{$senderName} marked your date night as done",
            'body' => "How was your {$plan->theme_emoji} {$plan->theme} evening? Add your own rating.",
            'data' => ['plan_id' => $plan->id],
        ]);
    }

    public function notifyPartnerChangedPlan(User $recipient, User $sender, DateNightPlan $plan, string $steer): void
    {
        $senderName = $sender->display_name ?? $sender->name;

        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::DateNightPlanReady,
            'title' => "{$senderName} changed your date night plan",
            'body' => "They asked for: \"{$steer}\". Take a look at the new plan.",
            'data' => ['plan_id' => $plan->id],
        ]);
    }

    public function notifyPartnerAddedDateNightToCalendar(
        User $recipient,
        User $sender,
        DateNightPlan $plan,
        CalendarEvent $event,
    ): void {
        $senderName = $sender->display_name ?? $sender->name;
        $when = $event->date->toFormattedDateString().($event->time ? " at {$event->time}" : '');
        $where = $event->location ? " at {$event->location}" : '';

        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::PartnerAddedDateNightToCalendar,
            'title' => "📅 {$senderName} scheduled your Date Night!",
            'body' => "Your {$plan->theme_emoji} {$plan->theme} evening is booked for {$when}{$where}. It's a date!",
            'data' => [
                'plan_id' => $plan->id,
                'calendar_event_id' => $event->id,
            ],
        ]);
    }
}
