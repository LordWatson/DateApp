<?php

namespace App\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogActivityOnQuestionnaireCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(QuestionnaireCompleted $event): void
    {
        $this->service->log($event->user, 'questionnaire_completed', $event->questionnaire, [
            'response_id' => $event->response->id,
        ]);
    }
}
