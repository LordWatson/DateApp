<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * High-level orchestrator for all AI interactions.
 *
 * Domain services and actions call the semantic methods on this class.
 * The service:
 *  - Loads the active prompt template from the database
 *  - Interpolates {{placeholders}} using the provided context
 *  - Builds an AIRequest with the configured defaults
 *  - Delegates to the injected AIProvider
 *  - Returns the provider's AIResponse (typed) untouched
 */
final readonly class AIService
{
    public function __construct(
        private AIProvider $provider,
        private ConfigRepository $config,
        private LoggerInterface $logger,
        private AIAnalyticsRecorder $analytics,
    ) {}

    public function generateDateNightPlan(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::DateNightPlan, $context);
    }

    public function generateConversationPrompt(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::ConversationPrompt, $context);
    }

    public function generateRelationshipInsight(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::RelationshipInsight, $context);
    }

    public function generateChallengeVariation(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::ChallengeVariation, $context);
    }

    public function enhanceThemeDescription(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::ThemeDescription, $context);
    }

    public function generateMomentCaption(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::MomentCaption, $context);
    }

    public function generateCompatibilitySummary(array $context = []): AIResponse
    {
        return $this->run(AIUseCase::CompatibilitySummary, $context);
    }

    /**
     * Public entry point that lets callers attach a subject model and
     * user id for analytics without changing the semantic methods above.
     *
     * @param  array<string, mixed>  $context
     */
    public function generate(
        AIUseCase $useCase,
        array $context = [],
        ?Model $subject = null,
        ?int $userId = null,
    ): AIResponse {
        return $this->run($useCase, $context, $subject, $userId);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function run(
        AIUseCase $useCase,
        array $context,
        ?Model $subject = null,
        ?int $userId = null,
    ): AIResponse {
        $template = $this->loadTemplate($useCase);

        $request = new AIRequest(
            useCase: $useCase,
            systemPrompt: $template->system_prompt,
            userPrompt: $this->interpolate($template->user_prompt_template, $context),
            temperature: (float) $this->config->get('ai.defaults.temperature'),
            // Per-template `max_tokens` override wins when set; otherwise fall
            // back to the global default. This lets verbose use cases like
            // `date_night_plan` reserve more output tokens without inflating
            // the budget for cheaper prompts.
            maxTokens: $template->max_tokens ?? (int) $this->config->get('ai.defaults.max_tokens'),
            timeout: (int) $this->config->get('ai.defaults.timeout'),
            retryAttempts: (int) $this->config->get('ai.defaults.retry_attempts'),
            context: $context,
            metadata: [
                'template_id' => $template->id,
                'template_version' => $template->version,
                'provider' => $this->provider->name(),
            ],
            responseFormat: (string) $this->config->get('ai.defaults.response_format', 'json_object'),
        );

        $startedAt = hrtime(true);
        $response = $this->provider->complete($request);
        $durationMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        if (! $response->successful) {
            $this->logger->warning('AI request failed.', [
                'use_case' => $useCase->value,
                'provider' => $this->provider->name(),
                'error_code' => $response->errorCode,
                'error_message' => $response->errorMessage,
            ]);
        }

        $this->analytics->record(
            request: $request,
            response: $response,
            durationMs: $durationMs,
            fallbackUsed: ! $response->successful,
            subject: $subject,
            userId: $userId,
        );

        return $response;
    }

    private function loadTemplate(AIUseCase $useCase): AiPromptTemplate
    {
        /** @var AiPromptTemplate|null $template */
        $template = AiPromptTemplate::query()
            ->where('name', $useCase->value)
            ->where('active', true)
            ->orderByDesc('version')
            ->first();

        if ($template === null) {
            throw new RuntimeException(
                "No active AI prompt template found for use case [{$useCase->value}]."
            );
        }

        return $template;
    }

    /**
     * Replaces {{key}} placeholders in the template with values from
     * the provided context. Non-scalar values are JSON encoded so that
     * complex context is still safely embeddable.
     *
     * @param  array<string, mixed>  $context
     */
    private function interpolate(string $template, array $context): string
    {
        if ($context === []) {
            return $template;
        }

        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = is_scalar($value) || $value === null
                ? (string) $value
                : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return strtr($template, $replacements);
    }
}
