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
 * DeepSeek AI provider adapter.
 *
 * DeepSeek exposes an OpenAI-compatible /chat/completions endpoint,
 * so we implement it directly on top of Laravel's HTTP client.
 * All configuration is read from config/ai.php (never hardcoded).
 */
final readonly class DeepSeekProvider implements AIProvider
{
    private const NAME = 'deepseek';

    private const ERROR_MISSING_API_KEY = 'missing_api_key';

    private const ERROR_TRANSPORT = 'transport_error';

    private const ERROR_HTTP_STATUS = 'http_error';

    private const ERROR_INVALID_PAYLOAD = 'invalid_payload';

    private const ERROR_INVALID_JSON = 'invalid_json';

    private const ERROR_RESPONSE_TRUNCATED = 'response_truncated';

    private const FINISH_REASON_LENGTH = 'length';

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
        $apiKey = (string) $this->config->get('ai.providers.deepseek.api_key', '');
        $baseUrl = (string) $this->config->get('ai.providers.deepseek.base_url');
        $model = (string) $this->config->get('ai.providers.deepseek.model');
        $endpoint = (string) $this->config->get('ai.providers.deepseek.chat_endpoint');
        $retryDelay = (int) $this->config->get('ai.defaults.retry_delay_ms', 250);

        if ($apiKey === '') {
            $this->logger->warning('DeepSeek API key is not configured.', [
                'use_case' => $request->useCase->value,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_MISSING_API_KEY,
                errorMessage: 'DEEPSEEK_API_KEY is not set.',
                model: $model,
            );
        }

        $payload = [
            'model' => $model,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
            'response_format' => ['type' => $request->responseFormat],
            'messages' => [
                ['role' => 'system', 'content' => $request->systemPrompt],
                ['role' => 'user', 'content' => $request->userPrompt],
            ],
        ];

        try {
            $response = $this->http
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($request->timeout)
                ->retry(
                    times: max(1, $request->retryAttempts),
                    sleepMilliseconds: $retryDelay,
                    when: static fn (Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->post(rtrim($baseUrl, '/').$endpoint, $payload);
        } catch (ConnectionException $e) {
            $this->logger->error('DeepSeek transport error.', [
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
            $this->logger->warning('DeepSeek returned non-2xx status.', [
                'use_case' => $request->useCase->value,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_HTTP_STATUS,
                errorMessage: 'DeepSeek returned HTTP '.$response->status(),
                model: $model,
                raw: ['status' => $response->status(), 'body' => $response->body()],
            );
        }

        $body = $response->json();

        if (! is_array($body) || ! isset($body['choices'][0]['message']['content'])) {
            $this->logger->warning('DeepSeek returned an unexpected payload shape.', [
                'use_case' => $request->useCase->value,
                'body' => $body,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_INVALID_PAYLOAD,
                errorMessage: 'DeepSeek response payload is malformed.',
                model: $model,
                raw: is_array($body) ? $body : [],
            );
        }

        $content = (string) $body['choices'][0]['message']['content'];
        $finishReason = (string) ($body['choices'][0]['finish_reason'] ?? '');

        // Detect max_tokens truncation BEFORE attempting to decode. DeepSeek
        // (like every OpenAI-compatible API) returns `finish_reason: "length"`
        // when the model hit the output-token cap mid-generation. The content
        // will almost always be structurally invalid JSON in that case, but
        // the actual bug is a budget issue — not a model formatting issue —
        // so we surface it as its own error code so callers/analytics can
        // distinguish "raise max_tokens" from "prompt the model differently".
        if ($finishReason === self::FINISH_REASON_LENGTH) {
            $this->logger->warning('DeepSeek response was truncated (finish_reason=length).', [
                'use_case' => $request->useCase->value,
                'max_tokens' => $request->maxTokens,
                'usage' => $body['usage'] ?? null,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_RESPONSE_TRUNCATED,
                errorMessage: 'DeepSeek response was truncated because max_tokens was reached.',
                model: (string) ($body['model'] ?? $model),
                raw: $body,
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            $this->logger->warning('DeepSeek response content was not valid JSON.', [
                'use_case' => $request->useCase->value,
                'finish_reason' => $finishReason,
                'content' => $content,
            ]);

            return AIResponse::failure(
                useCase: $request->useCase,
                errorCode: self::ERROR_INVALID_JSON,
                errorMessage: 'DeepSeek response content was not valid JSON.',
                model: $model,
                raw: $body,
            );
        }

        return AIResponse::success(
            useCase: $request->useCase,
            data: $decoded,
            model: (string) ($body['model'] ?? $model),
            usage: (array) ($body['usage'] ?? []),
            raw: $body,
        );
    }
}
