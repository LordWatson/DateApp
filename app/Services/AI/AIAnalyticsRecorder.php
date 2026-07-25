<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Models\AiGeneration;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Persists analytics for every AI generation attempt.
 *
 * Recording never affects the user flow. Any storage failure is
 * swallowed and logged, guaranteeing analytics is best-effort only.
 */
final readonly class AIAnalyticsRecorder
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        AIRequest $request,
        AIResponse $response,
        int $durationMs,
        bool $fallbackUsed,
        ?Model $subject = null,
        ?int $userId = null,
        array $meta = [],
    ): void {
        try {
            AiGeneration::create([
                'provider' => (string) ($request->metadata['provider'] ?? 'unknown'),
                'model' => $response->model !== '' ? $response->model : null,
                'feature' => $request->useCase->value,
                'prompt_template' => isset($request->metadata['template_id'])
                    ? (string) $request->useCase->value.':v'.($request->metadata['template_version'] ?? '?')
                    : null,
                'prompt_tokens' => (int) ($response->usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($response->usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($response->usage['total_tokens'] ?? 0),
                'duration_ms' => $durationMs,
                'successful' => $response->successful,
                'fallback_used' => $fallbackUsed,
                'error_code' => $response->errorCode,
                'error_message' => $response->errorMessage,
                'user_id' => $userId,
                'subject_type' => $subject !== null ? $subject::class : ($meta['subject_type'] ?? null),
                'subject_id' => $subject?->getKey() ?? ($meta['subject_id'] ?? null),
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('Failed to record AI analytics.', [
                'exception' => $e->getMessage(),
                'feature' => $request->useCase->value,
            ]);
        }
    }
}
