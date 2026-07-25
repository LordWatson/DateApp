<?php

declare(strict_types=1);

namespace App\DTOs\AI;

use App\Enums\AI\AIUseCase;

/**
 * Immutable request DTO passed to any AIProvider implementation.
 *
 * The DTO is deliberately provider-agnostic; provider adapters translate
 * it into their own wire format.
 */
final readonly class AIRequest
{
    /**
     * @param  array<string, mixed>  $context  Variables interpolated into the user prompt template.
     * @param  array<string, mixed>  $metadata  Optional tracing / logging metadata.
     */
    public function __construct(
        public AIUseCase $useCase,
        public string $systemPrompt,
        public string $userPrompt,
        public float $temperature,
        public int $maxTokens,
        public int $timeout,
        public int $retryAttempts,
        public array $context = [],
        public array $metadata = [],
        public string $responseFormat = 'json_object',
    ) {}
}
