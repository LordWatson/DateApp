<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Anthropic (Claude) provider adapter for the Messages API.
 *
 * Implemented on Laravel's HTTP client, like DeepSeekProvider, so no
 * SDK dependency is needed. All configuration is read from config/ai.php.
 */
final readonly class AnthropicProvider implements AIProvider
{
    private const NAME = 'anthropic';

    private const ERROR_MISSING_API_KEY = 'missing_api_key';

    private const ERROR_TRANSPORT = 'transport_error';

    private const ERROR_HTTP_STATUS = 'http_error';

    private const ERROR_INVALID_PAYLOAD = 'invalid_payload';

    private const ERROR_INVALID_JSON = 'invalid_json';

    private const ERROR_RESPONSE_TRUNCATED = 'response_truncated';

    private const ERROR_REFUSED = 'refused';

    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    public function __construct(
        private HttpFactory $http,
        private ConfigRepository $config,
        private LoggerInterface $logger,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function complete(AIRequest $request): AIResponse
    {
        $apiKey = (string) $this->config->get('ai.providers.anthropic.api_key', '');
        $baseUrl = (string) $this->config->get('ai.providers.anthropic.base_url');
        $model = (string) $this->config->get('ai.providers.anthropic.model');
        $endpoint = (string) $this->config->get('ai.providers.anthropic.messages_endpoint');
        $retryDelay = (int) $this->config->get('ai.defaults.retry_delay_ms', 250);
        $useFallbacks = (bool) $this->config->get('ai.providers.anthropic.fallbacks', true);

        if ($apiKey === '') {
            $this->logger->warning('Anthropic API key is not configured.', [
                'use_case' => $request->useCase->value,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_MISSING_API_KEY,
                errorMessage: 'ANTHROPIC_API_KEY is not set.',
                model: $model,
            );
        }

        // Thinking is always on for current Claude models and is billed against
        // max_tokens, so a budget sized for the JSON alone would truncate.
        $maxTokens = max(
            $request->maxTokens,
            (int) $this->config->get('ai.providers.anthropic.min_max_tokens', 16000),
        );

        // No `temperature`: current Claude models reject non-default sampling
        // parameters with a 400. Depth is controlled with `effort` instead.
        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $request->systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $request->userPrompt],
            ],
            'output_config' => [
                'effort' => (string) $this->config->get('ai.providers.anthropic.effort', 'medium'),
            ],
        ];

        $headers = [
            'x-api-key' => $apiKey,
            'anthropic-version' => (string) $this->config->get('ai.providers.anthropic.version'),
        ];

        // If a safety classifier declines, the API re-runs the request on a
        // fallback model inside the same call instead of returning a refusal.
        if ($useFallbacks) {
            $payload['fallbacks'] = 'default';
            $headers['anthropic-beta'] = self::FALLBACK_BETA;
        }

        try {
            $response = $this->http
                ->withHeaders($headers)
                ->acceptJson()
                ->asJson()
                ->timeout($request->timeout)
                ->retry(
                    times: max(1, $request->retryAttempts),
                    sleepMilliseconds: $retryDelay,
                    when: static fn (Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException
                            && ($e->response->serverError() || $e->response->status() === 429)),
                    throw: false,
                )
                ->post(rtrim($baseUrl, '/').$endpoint, $payload);
        } catch (ConnectionException $e) {
            $this->logger->error('Anthropic transport error.', [
                'use_case' => $request->useCase->value,
                'exception' => $e->getMessage(),
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_TRANSPORT,
                errorMessage: $e->getMessage(),
                model: $model,
            );
        }

        if (! $response->successful()) {
            $this->logger->warning('Anthropic returned non-2xx status.', [
                'use_case' => $request->useCase->value,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_HTTP_STATUS,
                errorMessage: 'Anthropic returned HTTP '.$response->status(),
                model: $model,
                raw: ['status' => $response->status(), 'body' => $response->body()],
            );
        }

        $body = $response->json();

        if (! is_array($body) || ! is_array($body['content'] ?? null)) {
            $this->logger->warning('Anthropic returned an unexpected payload shape.', [
                'use_case' => $request->useCase->value,
                'body' => $body,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_INVALID_PAYLOAD,
                errorMessage: 'Anthropic response payload is malformed.',
                model: $model,
                raw: is_array($body) ? $body : [],
            );
        }

        $servedBy = (string) ($body['model'] ?? $model);
        $stopReason = (string) ($body['stop_reason'] ?? '');

        if ($stopReason === 'refusal') {
            $this->logger->warning('Anthropic declined the request.', [
                'use_case' => $request->useCase->value,
                'stop_details' => $body['stop_details'] ?? null,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_REFUSED,
                errorMessage: 'Anthropic declined the request.',
                model: $servedBy,
                raw: $body,
            );
        }

        if ($stopReason === 'max_tokens') {
            $this->logger->warning('Anthropic response was truncated (stop_reason=max_tokens).', [
                'use_case' => $request->useCase->value,
                'max_tokens' => $maxTokens,
                'usage' => $body['usage'] ?? null,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_RESPONSE_TRUNCATED,
                errorMessage: 'Anthropic response was truncated because max_tokens was reached.',
                model: $servedBy,
                raw: $body,
            );
        }

        $text = '';

        foreach ($body['content'] as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        $decoded = json_decode($this->stripCodeFence($text), true);

        if (! is_array($decoded)) {
            $this->logger->warning('Anthropic response content was not valid JSON.', [
                'use_case' => $request->useCase->value,
                'stop_reason' => $stopReason,
                'content' => $text,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_INVALID_JSON,
                errorMessage: 'Anthropic response content was not valid JSON.',
                model: $servedBy,
                raw: $body,
            );
        }

        $inputTokens = (int) ($body['usage']['input_tokens'] ?? 0);
        $outputTokens = (int) ($body['usage']['output_tokens'] ?? 0);

        return AIResponse::success(
            useCase: $request->useCase,
            data: $decoded,
            model: $servedBy,
            // Same keys as the OpenAI-compatible providers, which analytics reads.
            usage: [
                'prompt_tokens' => $inputTokens,
                'completion_tokens' => $outputTokens,
                'total_tokens' => $inputTokens + $outputTokens,
            ],
            raw: $body,
        );
    }

    /**
     * The API has no JSON mode without a schema, so the model may wrap the object in a Markdown fence.
     */
    private function stripCodeFence(string $text): string
    {
        return trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
    }
}
