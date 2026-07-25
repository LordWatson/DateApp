<?php

declare(strict_types=1);

namespace App\DTOs\AI;

use App\Enums\AI\AIUseCase;

/**
 * Immutable response DTO returned by every AIProvider implementation.
 *
 * Callers must inspect `successful` before consuming `data`. When
 * `successful` is false, `errorCode` and `errorMessage` describe the
 * typed failure. `data` always contains the parsed JSON payload on
 * success (never a raw string).
 */
final readonly class AIResponse
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $raw  Raw provider payload for observability. Never leak outside logs.
     */
    public function __construct(
        public AIUseCase $useCase,
        public bool $successful,
        public array $data,
        public string $model,
        public array $usage = [],
        public array $raw = [],
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(
        AIUseCase $useCase,
        array $data,
        string $model,
        array $usage = [],
        array $raw = [],
    ): self {
        return new self(
            useCase: $useCase,
            successful: true,
            data: $data,
            model: $model,
            usage: $usage,
            raw: $raw,
        );
    }

    public static function failure(
        AIUseCase $useCase,
        string $errorCode,
        string $errorMessage,
        string $model = '',
        array $raw = [],
    ): self {
        return new self(
            useCase: $useCase,
            successful: false,
            data: [],
            model: $model,
            raw: $raw,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }
}
