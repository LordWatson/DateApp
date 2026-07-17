<?php

namespace App\Jobs;

use App\Actions\GenerateDateNightPlanAction;
use App\Enums\CompletionStatus;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateDateNightPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly User $userOne,
        public readonly User $userTwo,
        public readonly Questionnaire $questionnaire,
    ) {
        $this->onQueue('default');
    }

    public function handle(GenerateDateNightPlanAction $action): void
    {
        $latestOne = Response::where('user_id', $this->userOne->id)
            ->where('questionnaire_id', $this->questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->latest('completed_at')
            ->latest('id')
            ->first();

        $latestTwo = Response::where('user_id', $this->userTwo->id)
            ->where('questionnaire_id', $this->questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->latest('completed_at')
            ->latest('id')
            ->first();

        if (! $latestOne || ! $latestTwo) {
            return;
        }

        $latestIds = [$latestOne->id, $latestTwo->id];

        $existingPlan = DateNightPlan::where('questionnaire_id', $this->questionnaire->id)
            ->whereIn('partner_one_response_id', $latestIds)
            ->whereIn('partner_two_response_id', $latestIds)
            ->exists();

        if ($existingPlan) {
            return;
        }

        $action->execute($this->userOne, $this->userTwo, $this->questionnaire);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateDateNightPlanJob failed', [
            'user_one' => $this->userOne->id,
            'questionnaire' => $this->questionnaire->id,
            'error' => $e->getMessage(),
        ]);
    }
}
