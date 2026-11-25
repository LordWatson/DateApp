<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\User;
use App\Services\RuntimeSettings;
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
    public const DISABLED_ERROR_CODE = 'ai_disabled';

    public const NO_CONSENT_ERROR_CODE = 'ai_consent_missing';

    public function __construct(
        private AIProvider $provider,
        private ConfigRepository $config,
        private LoggerInterface $logger,
        private AIAnalyticsRecorder $analytics,
    ) {}

    /*
     * Every method below takes `$people`: the users whose answers, notes or
     * moments are in `$context`. The provider is only called when all of them
     * have agreed to AI processing. Pass [] only when the context holds no
     * personal data.
     */

    /**
     * @param  array<string, mixed>  $context
     * @param  list<User>  $people
     */
    public function generateDateNightPlan(array $context, array $people): AIResponse
    {
        return $this->run(AIUseCase::DateNightPlan, $context, $people);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<User>  $people
     */
    public function generateDateNightPlanSolo(array $context, array $people): AIResponse
    {
        return $this->run(AIUseCase::DateNightPlanSolo, $context, $people);
    }

    /**
     * Public entry point that lets callers attach a subject model and
     * user id for analytics without changing the semantic methods above.
     *
     * @param  array<string, mixed>  $context
     * @param  list<User>  $people
     */
    public function generate(
        AIUseCase $useCase,
        array $context,
        array $people,
        ?Model $subject = null,
        ?int $userId = null,
    ): AIResponse {
        return $this->run($useCase, $context, $people, $subject, $userId);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<User>  $people
     */
    private function run(
        AIUseCase $useCase,
        array $context,
        array $people,
        ?Model $subject = null,
        ?int $userId = null,
    ): AIResponse {
        // Nothing leaves the app unless everyone whose content is in the prompt has agreed.
        foreach ($people as $person) {
            if (! $person->hasAiConsent()) {
                return AIResponse::failure(
                    $useCase,
                    self::NO_CONSENT_ERROR_CODE,
                    'Not everyone whose content is in this request has agreed to AI processing.',
                    $this->provider->name(),
                );
            }
        }

        // Switched off by an admin: no provider call, and every caller uses its own fallback.
        if (! app(RuntimeSettings::class)->flag('ai_generation')) {
            return AIResponse::failure(
                $useCase,
                self::DISABLED_ERROR_CODE,
                'AI generation is switched off (feature flag ai_generation).',
                $this->provider->name(),
            );
        }

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
