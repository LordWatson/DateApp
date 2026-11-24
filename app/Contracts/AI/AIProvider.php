<?php

declare(strict_types=1);

namespace App\Contracts\AI;

use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;

/**
 * Contract implemented by every concrete AI provider (Anthropic, DeepSeek, Null,
 * and future OpenAI / Gemini adapters).
 *
 * Providers must:
 *  - Accept only AIRequest DTOs
 *  - Always return AIResponse DTOs (never raw arrays or strings)
 *  - Never throw for expected transport / validation failures; encode
 *    them as AIResponse::failure() with a stable errorCode.
 */
interface AIProvider
{
    public function complete(AIRequest $request): AIResponse;

    public function name(): string;
}
