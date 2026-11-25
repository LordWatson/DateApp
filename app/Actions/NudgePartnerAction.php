<?php

namespace App\Actions;

use App\Enums\CompletionStatus;
use App\Enums\NotificationType;
use App\Jobs\SendQuestionnaireCompletedNotificationJob;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Response;
use App\Services\QuestionnaireService;

/**
 * Reminds the partner that a couple attempt is waiting for their answers.
 */
class NudgePartnerAction
{
    public const HOURS_BETWEEN_NUDGES = 24;

    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
    ) {}

    /**
     * @return bool Whether a reminder was sent.
     */
    public function execute(Response $response): bool
    {
        if (! $this->canNudge($response)) {
            return false;
        }

        $sender = $response->user;
        $partner = $sender->partner;
        $questionnaire = $response->questionnaire;
        $senderName = $sender->display_name ?? $sender->name;

        AppNotification::create([
            'user_id' => $partner->id,
            'type' => NotificationType::PartnerNudge,
            'title' => "{$senderName} is waiting for your answers",
            'body' => "Answer \"{$questionnaire->title}\" and we'll plan a date that works for you both.",
            'data' => [
                'questionnaire_id' => $questionnaire->id,
                'questionnaire_slug' => $questionnaire->slug,
            ],
        ]);

        SendQuestionnaireCompletedNotificationJob::dispatch($partner, $sender, $questionnaire);

        $response->update(['partner_nudged_at' => now()]);

        return true;
    }

    public function canNudge(Response $response): bool
    {
        $response->loadMissing(['user.partner', 'questionnaire']);
        $partner = $response->user->partner;

        if ($response->status !== CompletionStatus::Completed || $response->isSolo() || ! $partner) {
            return false;
        }

        if ($response->partner_nudged_at?->gt(now()->subHours(self::HOURS_BETWEEN_NUDGES))) {
            return false;
        }

        $alreadyPlanned = DateNightPlan::query()
            ->where('partner_one_response_id', $response->id)
            ->orWhere('partner_two_response_id', $response->id)
            ->exists();

        // A partner who has already answered needs no reminder: the plan is on its way.
        return ! $alreadyPlanned
            && ! $this->questionnaireService->partnerHasFreshCompletedResponse($response->user, $partner, $response->questionnaire);
    }
}
