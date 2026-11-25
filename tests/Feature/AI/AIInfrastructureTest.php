<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Services\AI\AIService;
use App\Services\AI\AISettingsService;
use App\Services\AI\AnthropicProvider;
use App\Services\AI\DeepSeekProvider;
use App\Services\AI\NullAIProvider;
use Database\Seeders\AiPromptTemplateSeeder;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AIInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a known baseline configuration for every test.
        config()->set('ai.provider', 'deepseek');
        config()->set('ai.providers.deepseek.api_key', 'test-key');
        config()->set('ai.providers.deepseek.base_url', 'https://api.deepseek.test');
        config()->set('ai.providers.deepseek.model', 'deepseek-v4-flash');
        config()->set('ai.providers.deepseek.chat_endpoint', '/chat/completions');
        config()->set('ai.defaults.temperature', 0.7);
        config()->set('ai.defaults.max_tokens', 1200);
        config()->set('ai.defaults.timeout', 30);
        config()->set('ai.defaults.retry_attempts', 3);
        config()->set('ai.defaults.retry_delay_ms', 0);
        config()->set('ai.defaults.response_format', 'json_object');
    }

    public function test_config_file_is_loaded_with_expected_defaults(): void
    {
        $this->assertSame(0.7, config('ai.defaults.temperature'));
        $this->assertSame(1200, config('ai.defaults.max_tokens'));
        $this->assertSame(30, config('ai.defaults.timeout'));
        $this->assertSame(3, config('ai.defaults.retry_attempts'));
        $this->assertSame('json_object', config('ai.defaults.response_format'));
        $this->assertNotEmpty(config('ai.safety.system_rules'));
    }

    public function test_provider_binding_resolves_deepseek_by_default(): void
    {
        $this->refreshApplication();
        config()->set('ai.provider', 'deepseek');

        $this->assertInstanceOf(DeepSeekProvider::class, $this->app->make(AIProvider::class));
    }

    public function test_provider_binding_resolves_null_provider_when_configured(): void
    {
        $this->refreshApplication();
        config()->set('ai.provider', 'null');

        $this->assertInstanceOf(NullAIProvider::class, $this->app->make(AIProvider::class));
    }

    public function test_provider_binding_throws_for_unknown_provider(): void
    {
        $this->refreshApplication();
        config()->set('ai.provider', 'not-a-real-provider');

        $this->expectException(BindingResolutionException::class);

        $this->app->make(AIProvider::class);
    }

    public function test_seeder_registers_a_template_for_every_use_case(): void
    {
        $this->seed(AiPromptTemplateSeeder::class);

        foreach (AIUseCase::cases() as $useCase) {
            $this->assertDatabaseHas('ai_prompt_templates', [
                'name' => $useCase->value,
                'active' => true,
            ]);
        }
    }

    public function test_ai_service_loads_prompt_template_and_interpolates_context(): void
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'system_prompt' => 'system rules',
            'user_prompt_template' => 'Hello {{name}}, context: {{payload}}',
        ]);

        $captured = null;
        $this->app->instance(AIProvider::class, new class($captured) implements AIProvider
        {
            public function __construct(public ?AIRequest &$captured) {}

            public function name(): string
            {
                return 'spy';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->captured = $request;

                return AIResponse::success($request->useCase, ['ok' => true], 'spy');
            }
        });

        /** @var AIService $service */
        $service = $this->app->make(AIService::class);
        $response = $service->generateDateNightPlan([
            'name' => 'Alex',
            'payload' => ['mood' => 'calm'],
        ], []);

        $this->assertTrue($response->successful);
        $this->assertSame(['ok' => true], $response->data);
        $this->assertNotNull($captured);
        $this->assertSame(AIUseCase::DateNightPlan, $captured->useCase);
        $this->assertSame('system rules', $captured->systemPrompt);
        $this->assertStringContainsString('Hello Alex', $captured->userPrompt);
        $this->assertStringContainsString('"mood":"calm"', $captured->userPrompt);
        $this->assertSame(0.7, $captured->temperature);
        $this->assertSame(1200, $captured->maxTokens);
        $this->assertSame('json_object', $captured->responseFormat);
    }

    public function test_ai_service_throws_when_no_active_template_exists(): void
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->inactive()->create();

        $this->app->instance(AIProvider::class, new NullAIProvider);

        $this->expectException(RuntimeException::class);

        $this->app->make(AIService::class)->generateDateNightPlan([], []);
    }

    public function test_ai_service_uses_highest_active_version(): void
    {
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'version' => 1,
            'user_prompt_template' => 'v1',
        ]);
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'version' => 3,
            'user_prompt_template' => 'v3',
        ]);

        $captured = null;
        $this->app->instance(AIProvider::class, new class($captured) implements AIProvider
        {
            public function __construct(public ?AIRequest &$captured) {}

            public function name(): string
            {
                return 'spy';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->captured = $request;

                return AIResponse::success($request->useCase, [], 'spy');
            }
        });

        $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertSame('v3', $captured->userPrompt);
    }

    public function test_deepseek_provider_parses_successful_json_response(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response([
                'model' => 'deepseek-v4-flash',
                'usage' => ['total_tokens' => 42],
                'choices' => [[
                    'message' => ['content' => json_encode(['prompt' => 'Hi'])],
                ]],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertTrue($response->successful);
        $this->assertSame(['prompt' => 'Hi'], $response->data);
        $this->assertSame('deepseek-v4-flash', $response->model);
        $this->assertSame(42, $response->usage['total_tokens']);
    }

    public function test_deepseek_provider_returns_typed_error_when_json_content_is_invalid(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response([
                'model' => 'deepseek-v4-flash',
                'choices' => [[
                    'message' => ['content' => 'not json at all'],
                ]],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertFalse($response->successful);
        $this->assertSame('invalid_json', $response->errorCode);
        $this->assertSame([], $response->data);
    }

    public function test_deepseek_provider_returns_typed_error_when_response_is_truncated_by_max_tokens(): void
    {
        // Simulates the production bug: DeepSeek stops mid-string because
        // max_tokens was reached (finish_reason=length). The content is not
        // valid JSON but the root cause is a token-budget issue, so the
        // provider must surface a dedicated `response_truncated` error code
        // rather than the generic `invalid_json`.
        Http::fake([
            'api.deepseek.test/*' => Http::response([
                'model' => 'deepseek-v4-flash',
                'usage' => ['completion_tokens' => 1200, 'total_tokens' => 1500],
                'choices' => [[
                    'finish_reason' => 'length',
                    'message' => ['content' => '{"theme": "Cosy Pub", "summary": "Halfway thro'],
                ]],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertFalse($response->successful);
        $this->assertSame('response_truncated', $response->errorCode);
        $this->assertSame([], $response->data);
    }

    public function test_ai_service_uses_per_template_max_tokens_override_when_set(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response([
                'choices' => [['message' => ['content' => '{"prompt":"ok"}']]],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'max_tokens' => 3000,
        ]);

        $this->app->make(AIService::class)->generateDateNightPlan([], []);

        Http::assertSent(static fn ($request): bool => $request->data()['max_tokens'] === 3000);
    }

    public function test_ai_service_falls_back_to_config_max_tokens_when_template_override_is_null(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response([
                'choices' => [['message' => ['content' => '{"prompt":"ok"}']]],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'max_tokens' => null,
        ]);

        $this->app->make(AIService::class)->generateDateNightPlan([], []);

        Http::assertSent(static fn ($request): bool => $request->data()['max_tokens'] === 1200);
    }

    public function test_deepseek_provider_returns_typed_error_when_payload_shape_is_wrong(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response(['unexpected' => true], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertFalse($response->successful);
        $this->assertSame('invalid_payload', $response->errorCode);
    }

    public function test_deepseek_provider_returns_typed_error_on_non_2xx(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response(['error' => 'nope'], 500),
        ]);

        config()->set('ai.defaults.retry_attempts', 1);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertFalse($response->successful);
        $this->assertSame('http_error', $response->errorCode);
    }

    public function test_deepseek_provider_reports_missing_api_key(): void
    {
        config()->set('ai.providers.deepseek.api_key', '');
        Http::fake();

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertFalse($response->successful);
        $this->assertSame('missing_api_key', $response->errorCode);
        Http::assertNothingSent();
    }

    public function test_deepseek_provider_sends_expected_openai_compatible_payload(): void
    {
        Http::fake([
            'api.deepseek.test/*' => Http::response([
                'choices' => [['message' => ['content' => '{"prompt":"ok"}']]],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'system_prompt' => 'sys',
            'user_prompt_template' => 'usr',
        ]);

        $this->app->make(AIService::class)->generateDateNightPlan([], []);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $request->url() === 'https://api.deepseek.test/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['model'] === 'deepseek-v4-flash'
                && $body['temperature'] === 0.7
                && $body['max_tokens'] === 1200
                && $body['response_format'] === ['type' => 'json_object']
                && $body['messages'][0] === ['role' => 'system', 'content' => 'sys']
                && $body['messages'][1] === ['role' => 'user', 'content' => 'usr'];
        });
    }

    public function test_null_provider_returns_deterministic_success_dto(): void
    {
        $this->app->instance(AIProvider::class, new NullAIProvider);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan(['x' => 1], []);

        $this->assertTrue($response->successful);
        $this->assertSame('null', $response->model);
        $this->assertArrayHasKey('summary', $response->data);
    }

    public function test_ai_request_dto_is_immutable_and_typed(): void
    {
        $request = new AIRequest(
            useCase: AIUseCase::DateNightPlan,
            systemPrompt: 's',
            userPrompt: 'u',
            temperature: 0.5,
            maxTokens: 100,
            timeout: 10,
            retryAttempts: 2,
        );

        $this->assertSame(AIUseCase::DateNightPlan, $request->useCase);
        $this->assertSame(0.5, $request->temperature);
        $this->assertSame('json_object', $request->responseFormat);

        $reflection = new \ReflectionClass($request);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_ai_response_failure_factory_produces_typed_error(): void
    {
        $response = AIResponse::failure(AIUseCase::DateNightPlan, 'boom', 'went wrong');

        $this->assertFalse($response->successful);
        $this->assertSame([], $response->data);
        $this->assertSame('boom', $response->errorCode);
        $this->assertSame('went wrong', $response->errorMessage);
    }

    public function test_provider_binding_resolves_anthropic_by_default(): void
    {
        $this->assertSame('anthropic', (require config_path('ai.php'))['provider'] ?? null);

        $this->useAnthropic();

        $this->assertInstanceOf(AnthropicProvider::class, $this->app->make(AIProvider::class));
    }

    public function test_anthropic_provider_sends_expected_messages_api_payload(): void
    {
        $this->useAnthropic();
        Http::fake(['api.anthropic.test/*' => Http::response($this->anthropicBody('{"prompt":"ok"}'), 200)]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create([
            'system_prompt' => 'sys',
            'user_prompt_template' => 'usr',
        ]);

        $this->app->make(AIService::class)->generateDateNightPlan([], []);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $request->url() === 'https://api.anthropic.test/v1/messages'
                && $request->hasHeader('x-api-key', 'test-key')
                && $request->hasHeader('anthropic-version', '2026-10-21')
                && $request->hasHeader('anthropic-beta', 'server-side-fallback-2026-07-01')
                && $body['model'] === 'claude-test'
                && $body['max_tokens'] === 16000
                && $body['system'] === 'sys'
                && $body['messages'] === [['role' => 'user', 'content' => 'usr']]
                && $body['output_config'] === ['effort' => 'medium']
                && $body['fallbacks'] === 'default'
                && ! array_key_exists('temperature', $body);
        });
    }

    public function test_anthropic_provider_parses_fenced_json_and_maps_usage(): void
    {
        $this->useAnthropic();
        Http::fake([
            'api.anthropic.test/*' => Http::response([
                'model' => 'claude-test',
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'thinking', 'thinking' => ''],
                    ['type' => 'text', 'text' => "```json\n{\"prompt\":\"Hi\"}\n```"],
                ],
                'usage' => ['input_tokens' => 30, 'output_tokens' => 12],
            ], 200),
        ]);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertTrue($response->successful);
        $this->assertSame(['prompt' => 'Hi'], $response->data);
        $this->assertSame('claude-test', $response->model);
        $this->assertSame(
            ['prompt_tokens' => 30, 'completion_tokens' => 12, 'total_tokens' => 42],
            $response->usage,
        );
    }

    public function test_anthropic_provider_returns_typed_errors_for_truncation_refusal_and_bad_json(): void
    {
        $this->useAnthropic();
        Http::fakeSequence('api.anthropic.test/*')
            ->push($this->anthropicBody('{"prompt":"Hel', 'max_tokens'), 200)
            ->push($this->anthropicBody('', 'refusal'), 200)
            ->push($this->anthropicBody('Sorry, here is some prose.'), 200)
            ->push(['unexpected' => true], 200)
            ->push(['type' => 'error'], 400);

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();
        $service = $this->app->make(AIService::class);

        foreach (['response_truncated', 'refused', 'invalid_json', 'invalid_payload', 'http_error'] as $expected) {
            $response = $service->generateDateNightPlan([], []);

            $this->assertFalse($response->successful);
            $this->assertSame($expected, $response->errorCode);
        }
    }

    public function test_anthropic_provider_reports_missing_api_key(): void
    {
        $this->useAnthropic();
        config()->set('ai.providers.anthropic.api_key', '');
        Http::fake();

        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $response = $this->app->make(AIService::class)->generateDateNightPlan([], []);

        $this->assertSame('missing_api_key', $response->errorCode);
        Http::assertNothingSent();
    }

    public function test_admin_model_setting_applies_to_the_selected_provider(): void
    {
        $settings = $this->app->make(AISettingsService::class);

        $settings->update(['provider' => 'anthropic', 'model' => 'claude-custom']);

        $this->assertSame('claude-custom', config('ai.providers.anthropic.model'));
        $this->assertSame('deepseek-v4-flash', config('ai.providers.deepseek.model'));

        config()->set('ai.provider', 'deepseek');
        config()->set('ai.providers.anthropic.model', 'reset');
        $settings->hydrateConfig();

        $this->assertSame('anthropic', config('ai.provider'));
        $this->assertSame('claude-custom', config('ai.providers.anthropic.model'));
        $this->assertSame('claude-custom', $settings->current()['model']);
    }

    private function useAnthropic(): void
    {
        config()->set('ai.provider', 'anthropic');
        config()->set('ai.providers.anthropic.api_key', 'test-key');
        config()->set('ai.providers.anthropic.base_url', 'https://api.anthropic.test');
        config()->set('ai.providers.anthropic.model', 'claude-test');
        config()->set('ai.providers.anthropic.effort', 'medium');
        config()->set('ai.providers.anthropic.min_max_tokens', 16000);
        config()->set('ai.providers.anthropic.fallbacks', true);
        $this->app->forgetInstance(AIProvider::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function anthropicBody(string $text, string $stopReason = 'end_turn'): array
    {
        return [
            'model' => 'claude-test',
            'stop_reason' => $stopReason,
            'content' => [['type' => 'text', 'text' => $text]],
            'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
        ];
    }
}
