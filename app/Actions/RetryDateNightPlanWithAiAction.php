<?php

namespace App\Actions;

use App\Models\DateNightPlan;
use App\Services\CompatibilityService;
use App\Services\DateNightGeneratorService;

class RetryDateNightPlanWithAiAction
{
    public function __construct(
        private readonly DateNightGeneratorService $generatorService,
        private readonly CompatibilityService $compatibilityService,
    ) {}

    /**
     * Re-run the AI enhancement for a plan that was saved with the rule-based
     * wording, or, with a steer, rewrite any plan in the direction asked for.
     *
     * @return bool True when the AI answered and the plan was updated in place.
     */
    public function execute(DateNightPlan $plan, ?string $steer = null): bool
    {
        if ($steer === null && ! $plan->fallback_used) {
            return false;
        }

        $plan->loadMissing(['questionnaire', 'partnerOneResponse.user', 'partnerTwoResponse']);

        $compatibility = $plan->is_solo ? null : $this->compatibilityService->calculate(
            $plan->partnerOneResponse->user,
            $plan->questionnaire,
            $plan->partnerOneResponse,
            $plan->partnerTwoResponse,
        );

        $rewritten = $this->generatorService->retryEnhancement(
            $plan,
            $compatibility ?? ['percentage' => $plan->compatibility_score, 'matched' => [], 'different' => []],
            $steer,
        );

        if ($rewritten && $steer !== null) {
            $plan->update(['last_steer' => $steer, 'regeneration_count' => $plan->regeneration_count + 1]);
        }

        return $rewritten;
    }
}
