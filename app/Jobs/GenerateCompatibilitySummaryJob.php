<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AI\AIUseCase;
use App\Models\DateNightPlan;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Produces a narrative compatibility summary for a plan.
 *
 * Compatibility SCORE is calculated deterministically elsewhere.
 * This job only generates the accompanying prose. Idempotent via
 * a per-plan cache key.
 */
final class GenerateCompatibilitySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 45;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $planId,
        public readonly int $ttlSeconds = 604800,
    ) {
        $this->onQueue('ai');
    }

    public function cacheKey(): string
    {
        return "ai:compat_summary:plan:{$this->planId}";
    }

    public function handle(AIService $ai): void
    {
        $key = $this->cacheKey();
        if (Cache::has($key)) {
            return;
        }

        /** @var DateNightPlan|null $plan */
        $plan = DateNightPlan::find($this->planId);
        if ($plan === null) {
            return;
        }

        try {
            $response = $ai->generate(
                useCase: AIUseCase::CompatibilitySummary,
                context: [
                    'compatibility_score' => $plan->compatibility_score,
                    'theme' => $plan->theme,
                ],
                subject: $plan,
            );
        } catch (Throwable $e) {
            Log::warning('GenerateCompatibilitySummaryJob: AI call threw.', [
                'plan_id' => $plan->id,
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful) {
            return;
        }

        Cache::put($key, $response->data, $this->ttlSeconds);
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateCompatibilitySummaryJob permanently failed.', [
            'plan_id' => $this->planId,
            'error' => $e->getMessage(),
        ]);
    }
}
