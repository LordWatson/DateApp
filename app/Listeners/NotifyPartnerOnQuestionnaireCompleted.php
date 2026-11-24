<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\QuestionnaireCompleted;
use App\Jobs\SendQuestionnaireCompletedNotificationJob;
use App\Models\AppNotification;
use App\Services\QuestionnaireService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPartnerOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
    ) {}

    public function handle(QuestionnaireCompleted $event): void
    {
        if ($event->response->isSolo()) {
            return;
        }

        $partner = $event->user->partner;

        if (! $partner) {
            return;
        }

        $senderName = $event->user->display_name ?? $event->user->name;

        // The partner still has to answer when they have nothing waiting to be paired with this attempt.
        $partnerStillToAnswer = ! $this->questionnaireService->partnerHasFreshCompletedResponse(
            $event->user,
            $partner,
            $event->questionnaire,
        );

        AppNotification::create([
            'user_id' => $partner->id,
            'type' => NotificationType::PartnerCompleted,
            'title' => $partnerStillToAnswer
                ? "{$senderName} wants to plan a date with you"
                : "💕 {$senderName} completed the questionnaire!",
            'body' => $partnerStillToAnswer
                ? "Answer \"{$event->questionnaire->title}\" and we'll plan a date that works for you both."
                : "Your partner has finished \"{$event->questionnaire->title}\". Check back soon for your compatibility results.",
            'data' => [
                'questionnaire_id' => $event->questionnaire->id,
                'questionnaire_slug' => $event->questionnaire->slug,
            ],
        ]);

        SendQuestionnaireCompletedNotificationJob::dispatch($partner, $event->user, $event->questionnaire);
    }
}
