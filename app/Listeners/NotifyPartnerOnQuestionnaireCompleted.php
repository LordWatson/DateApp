<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\QuestionnaireCompleted;
use App\Jobs\SendQuestionnaireCompletedNotificationJob;
use App\Models\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPartnerOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(QuestionnaireCompleted $event): void
    {
        $partner = $event->user->partner;

        if (! $partner) {
            return;
        }

        $senderName = $event->user->display_name ?? $event->user->name;

        AppNotification::create([
            'user_id' => $partner->id,
            'type' => NotificationType::PartnerCompleted,
            'title' => "💕 {$senderName} completed the questionnaire!",
            'body' => "Your partner has finished \"{$event->questionnaire->title}\". Check back soon for your compatibility results.",
            'data' => ['questionnaire_id' => $event->questionnaire->id],
        ]);

        SendQuestionnaireCompletedNotificationJob::dispatch($partner, $event->user, $event->questionnaire);
    }
}
