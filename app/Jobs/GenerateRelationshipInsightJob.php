<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AI\AIUseCase;
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
 * Generates a relationship insight for a couple.
 *
 * Idempotent via cache key; graceful on failure (silent, logged).
 */
final class GenerateRelationshipInsightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [15, 45, 120];

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly int $coupleId,
        public readonly string $cacheKey,
        public readonly array $context = [],
        public readonly int $ttlSeconds = 604800,
    ) {
        $this->onQueue('ai');
    }

    public function handle(AIService $ai): void
    {
        if (Cache::has($this->cacheKey)) {
            return;
        }

        try {
            $response = $ai->generate(
                useCase: AIUseCase::RelationshipInsight,
                context: ['couple_id' => $this->coupleId, ...$this->context],
            );
        } catch (Throwable $e) {
            Log::warning('GenerateRelationshipInsightJob: AI call threw.', [
                'couple_id' => $this->coupleId,
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful) {
            return;
        }

        Cache::put($this->cacheKey, $response->data, $this->ttlSeconds);
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateRelationshipInsightJob permanently failed.', [
            'couple_id' => $this->coupleId,
            'error' => $e->getMessage(),
        ]);
    }
}
