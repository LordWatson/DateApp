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
        $bothCompleted = Response::where('questionnaire_id', $this->questionnaire->id)
            ->whereIn('user_id', [$this->userOne->id, $this->userTwo->id])
            ->where('status', CompletionStatus::Completed)
            ->count() === 2;

        if (! $bothCompleted) {
            return;
        }

        $existingPlan = DateNightPlan::where('questionnaire_id', $this->questionnaire->id)
            ->where(function ($q): void {
                $q->whereHas('partnerOneResponse', fn ($r) => $r->whereIn('user_id', [$this->userOne->id, $this->userTwo->id]))
                    ->orWhereHas('partnerTwoResponse', fn ($r) => $r->whereIn('user_id', [$this->userOne->id, $this->userTwo->id]));
            })
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
