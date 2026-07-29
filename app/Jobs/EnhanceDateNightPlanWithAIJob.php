<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SummariseCoupleAnswersAction;
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
        'theme',
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

    public function handle(AIService $ai, SummariseCoupleAnswersAction $summariseCoupleAnswers): void
    {
        /** @var DateNightPlan|null $plan */
        $plan = DateNightPlan::with([
            'questionnaire',
            'partnerOneResponse.user',
            'partnerOneResponse.answers.question',
            'partnerOneResponse.answers.questionOption',
            'partnerTwoResponse.user',
            'partnerTwoResponse.answers.question',
            'partnerTwoResponse.answers.questionOption',
        ])->find($this->planId);
        if ($plan === null || $plan->ai_enhanced) {
            return;
        }

        $coupleSummary = null;
        if ($plan->questionnaire !== null
            && $plan->partnerOneResponse !== null
            && $plan->partnerTwoResponse !== null
        ) {
            $coupleSummary = $summariseCoupleAnswers->execute(
                $plan->questionnaire,
                $plan->partnerOneResponse,
                $plan->partnerTwoResponse,
            );
        }

        $contextPayload = [
            'plan' => $plan->only([...self::ENHANCEABLE_FIELDS, 'theme']),
            'compatibility_score' => $plan->compatibility_score,
            'enhanceable_fields' => self::ENHANCEABLE_FIELDS,
            'questionnaire' => $coupleSummary['questionnaire'] ?? null,
            'couple_answers' => $coupleSummary === null
                ? null
                : [
                    'partner_one' => $coupleSummary['partner_one'],
                    'partner_two' => $coupleSummary['partner_two'],
                ],
        ];

        try {
            $response = $ai->generateDateNightPlan([
                ...$contextPayload,
                // Also expose the full payload under `context` so the
                // {{context}} placeholder in the prompt template resolves to
                // the complete structured context (questionnaire + answers).
                'context' => $contextPayload,
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
