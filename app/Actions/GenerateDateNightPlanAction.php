<?php

namespace App\Actions;

use App\Enums\CompletionStatus;
use App\Events\DateNightPlanGenerated;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\CompatibilityService;
use App\Services\DateNightGeneratorService;

class GenerateDateNightPlanAction
{
    public function __construct(
        private readonly CompatibilityService $compatibilityService,
        private readonly DateNightGeneratorService $generatorService,
    ) {}

    public function execute(
        User $userOne,
        User $userTwo,
        Questionnaire $questionnaire,
    ): DateNightPlan {
        $responseOne = Response::with('answers.questionOption')
            ->where('user_id', $userOne->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->latest('completed_at')
            ->latest('id')
            ->firstOrFail();

        $responseTwo = Response::with('answers.questionOption')
            ->where('user_id', $userTwo->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->latest('completed_at')
            ->latest('id')
            ->firstOrFail();

        $compatibility = $this->compatibilityService->calculate($userOne, $questionnaire);

        $plan = $this->generatorService->generate(
            $responseOne,
            $responseTwo,
            $compatibility ?? ['percentage' => 0, 'matched' => [], 'different' => []],
            $questionnaire,
        );

        DateNightPlanGenerated::dispatch($userOne, $userTwo, $plan);

        return $plan;
    }
}
