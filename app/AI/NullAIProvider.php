<?php

namespace App\AI;

use App\Contracts\AIProvider;

class NullAIProvider implements AIProvider
{
    public function isConfigured(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function enhancePlan(array $plan, array $context): array
    {
        return $plan;
    }
}
