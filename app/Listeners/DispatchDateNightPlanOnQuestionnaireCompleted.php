<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Jobs\GenerateDateNightPlanJob;
use App\Services\QuestionnaireService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class DispatchDateNightPlanOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
    ) {}

    public function handle(QuestionnaireCompleted $event): void
    {
        $partner = $event->user->partner;

        if (! $partner) {
            return;
        }

        if (! $this->questionnaireService->partnerHasFreshCompletedResponse($event->user, $partner, $event->questionnaire)) {
            return;
        }

        GenerateDateNightPlanJob::dispatch($event->user, $partner, $event->questionnaire);
    }
}
