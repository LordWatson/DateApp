<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;

/**
 * Deterministic no-op AI provider.
 *
 * Used when AI_PROVIDER=null (e.g. in tests, CI, or local environments
 * without an API key). It never performs network I/O and always returns
 * a well-formed AIResponse suitable for downstream consumers.
 */
final class NullAIProvider implements AIProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function complete(AIRequest $request): AIResponse
    {
        return AIResponse::success(
            useCase: $request->useCase,
            data: $this->stubData($request->useCase),
            model: 'null',
            usage: ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function stubData(AIUseCase $useCase): array
    {
        return match ($useCase) {
            AIUseCase::DateNightPlan => [
                'title' => 'A gentle evening in',
                'summary' => 'A calm, connection-focused evening at home.',
                'activities' => [],
            ],
            AIUseCase::ConversationPrompt => [
                'prompt' => 'What made you smile this week?',
            ],
            AIUseCase::RelationshipInsight => [
                'headline' => 'You value quality time together.',
                'body' => 'Consider a distraction-free evening this week.',
            ],
            AIUseCase::CompatibilitySummary => [
                'summary' => 'You share many values around connection.',
                'strengths' => [],
                'growth_areas' => [],
            ],
            AIUseCase::ChallengeVariation => [
                'variation' => 'Swap the activity for a shared playlist.',
            ],
            AIUseCase::ThemeDescription => [
                'description' => 'A warm, romantic and playful theme.',
            ],
            AIUseCase::MomentCaption => [
                'caption' => 'A little moment worth remembering.',
            ],
        };
    }
}
