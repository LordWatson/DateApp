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
 * Generates a fresh conversation prompt for a couple.
 *
 * Result is cached under a per-couple/day key so:
 *  - Repeat dispatches are cheap (idempotent).
 *  - AI failures never break the user flow — the cache simply
 *    stays empty and callers fall back to deterministic prompts.
 */
final class GenerateConversationPromptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 45;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $coupleId,
        public readonly string $cacheKey,
        public readonly int $ttlSeconds = 86400,
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
                useCase: AIUseCase::ConversationPrompt,
                context: ['couple_id' => $this->coupleId],
            );
        } catch (Throwable $e) {
            Log::warning('GenerateConversationPromptJob: AI call threw.', [
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
        Log::error('GenerateConversationPromptJob permanently failed.', [
            'couple_id' => $this->coupleId,
            'error' => $e->getMessage(),
        ]);
    }
}
