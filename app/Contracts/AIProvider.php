<?php

namespace App\Contracts;

interface AIProvider
{
    public function isConfigured(): bool;

    /**
     * Enhance the wording of a date night plan while preserving theme and constraints.
     *
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function enhancePlan(array $plan, array $context): array;
}
