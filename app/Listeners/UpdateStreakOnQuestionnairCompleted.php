<?php

namespace App\Listeners;

use App\Actions\UpdateStreakAction;
use App\Events\QuestionnaireCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

final class UpdateStreakOnQuestionnairCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(
        private readonly UpdateStreakAction $action,
    ) {}

    public function handle(QuestionnaireCompleted $event): void
    {
        $this->action->execute($event->user);
    }
}
