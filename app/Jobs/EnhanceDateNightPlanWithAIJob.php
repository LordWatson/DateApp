<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DateNightPlan;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Enhances an already-persisted DateNightPlan's wording via AI.
 *
 * Idempotent: skips work if `ai_enhanced` is already true.
 * Graceful: AI failures never mutate the plan; only `fallback_used`
 * is toggled so the frontend can render honestly.
 */
final class EnhanceDateNightPlanWithAIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    private const ENHANCEABLE_FIELDS = [
        'summary',
        'meal_suggestion',
        'drink_suggestion',
        'music_vibe',
        'atmosphere',
        'activity',
        'conversation_prompt',
        'romantic_challenge',
    ];

    public function __construct(
        public readonly int $planId,
    ) {
        $this->onQueue('ai');
    }

    public function handle(AIService $ai): void
    {
        /** @var DateNightPlan|null $plan */
        $plan = DateNightPlan::find($this->planId);
        if ($plan === null || $plan->ai_enhanced) {
            return;
        }

        try {
            $response = $ai->generateDateNightPlan([
                'plan' => $plan->only([...self::ENHANCEABLE_FIELDS, 'theme']),
                'compatibility_score' => $plan->compatibility_score,
                'enhanceable_fields' => self::ENHANCEABLE_FIELDS,
            ]);
        } catch (Throwable $e) {
            Log::warning('EnhanceDateNightPlanWithAIJob: AI call threw; fallback recorded.', [
                'plan_id' => $plan->id,
                'exception' => $e->getMessage(),
            ]);
            $plan->forceFill(['fallback_used' => true])->save();

            return;
        }

        if (! $response->successful) {
            $plan->forceFill(['fallback_used' => true])->save();

            return;
        }

        $updates = [];
        foreach (self::ENHANCEABLE_FIELDS as $field) {
            $value = $response->data[$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $updates[$field] = $value;
            }
        }

        if ($updates !== []) {
            $updates['ai_enhanced'] = true;
            $updates['fallback_used'] = false;
            $plan->forceFill($updates)->save();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('EnhanceDateNightPlanWithAIJob permanently failed.', [
            'plan_id' => $this->planId,
            'error' => $e->getMessage(),
        ]);
    }
}
