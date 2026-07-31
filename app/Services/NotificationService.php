<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\User;

class NotificationService
{
    public function notifyPartnerCompleted(User $recipient, User $sender, string $questionnaireName): void
    {
        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::PartnerCompleted,
            'title' => '🎉 '.($sender->display_name ?? $sender->name).' completed the questionnaire!',
            'body' => "Your partner has finished \"{$questionnaireName}\". Check back soon for your compatibility results.",
            'data' => ['questionnaire' => $questionnaireName],
        ]);
    }

    public function notifyDateNightPlanReady(User $recipient, DateNightPlan $plan): void
    {
        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::DateNightPlanReady,
            'title' => '❤️ Your Date Night plan is ready!',
            'body' => "Your personalised {$plan->theme_emoji} {$plan->theme} evening has been generated. Tap to view your plan.",
            'data' => ['plan_id' => $plan->id],
        ]);
    }

    public function notifyLoveNoteReceived(User $recipient, LoveNote $note): void
    {
        AppNotification::create([
            'user_id' => $recipient->id,
            'type' => NotificationType::LoveNoteReceived,
            'title' => '💌 You received a love note!',
            'body' => $note->message,
            'data' => ['love_note_id' => $note->id],
        ]);
    }

    public function notifyPartnerViewedPlan(User $recipient, DateNightPlan $plan): void
    {
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
}
