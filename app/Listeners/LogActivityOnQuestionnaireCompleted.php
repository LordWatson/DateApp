<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Services\ActivityLogService;

final class LogActivityOnQuestionnaireCompleted
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(QuestionnaireCompleted $event): void
    {
        $this->service->log($event->user, 'questionnaire_completed', $event->questionnaire, [
            'response_id' => $event->response->id,
        ]);
    }
}
