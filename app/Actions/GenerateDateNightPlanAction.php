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
        ?User $userTwo,
        Questionnaire $questionnaire,
    ): DateNightPlan {
        $solo = $userTwo === null || $questionnaire->forcesSolo();

        $responseOne = Response::with('answers.questionOption')
            ->where('user_id', $userOne->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->inMode($questionnaire, $solo)
            ->latest('completed_at')
            ->latest('id')
            ->firstOrFail();

        // Solo attempts: the same response is used for both refs and
        // compatibility is not meaningful, so it is skipped.
        if ($solo) {
            // A solo questionnaire is filled out by one user, but if they have
            // a partner the resulting date night must appear on the partner's
            // side too (history, notifications, emails).
            $partner = $userOne->activePartner();

            $plan = $this->generatorService->generate(
                $responseOne,
                null,
                ['percentage' => 100, 'matched' => [], 'different' => []],
                $questionnaire,
                $partner,
            );

            DateNightPlanGenerated::dispatch($userOne, $partner, $plan);

            return $plan;
        }

        $responseTwo = Response::with('answers.questionOption')
            ->where('user_id', $userTwo->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->inMode($questionnaire, false)
            ->latest('completed_at')
            ->latest('id')
            ->firstOrFail();

        $compatibility = $this->compatibilityService->calculate($userOne, $questionnaire, $responseOne, $responseTwo);

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
