<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\Enums\AI\AIUseCase;
use App\Http\Requests\Admin\UpdateAiSettingsRequest;
use App\Models\AiGeneration;
use App\Models\AiPromptTemplate;
use App\Services\AdminAuditService;
use App\Services\AI\AIService;
use App\Services\AI\AISettingsService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Admin panel for the AI subsystem: runtime settings, test connection,
 * prompt preview, and usage analytics.
 *
 * All AI work happens via AIService / AIProvider — this controller
 * never talks to a vendor SDK directly.
 */
final class AdminAiSettingController extends AdminController
{
    public function __construct(
        private readonly AISettingsService $settings,
        private readonly AdminAuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/ai-settings/Index', [
            'settings' => $this->settings->current(),
            'analytics' => $this->analyticsSnapshot(),
            'providers' => ['deepseek', 'null'],
            'useCases' => array_map(static fn (AIUseCase $c) => $c->value, AIUseCase::cases()),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $before = $this->settings->current();
        $this->settings->update($request->validated());
        $this->audit->log(
            action: 'ai.settings.updated',
            before: $before,
            after: $this->settings->current(),
        );

        return back()->with('success', 'AI settings updated.');
    }

    public function testConnection(AIProvider $provider, ConfigRepository $config): JsonResponse
    {
        $request = new AIRequest(
            useCase: AIUseCase::DateNightPlan,
            systemPrompt: 'You are a health-check probe. Respond with the JSON: {"ok":true}.',
            userPrompt: 'ping',
            temperature: 0.0,
            maxTokens: 16,
            timeout: (int) $config->get('ai.defaults.timeout', 30),
            retryAttempts: 1,
        );

        try {
            $response = $provider->complete($request);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'provider' => $provider->name(),
                'error' => $e->getMessage(),
            ], 200);
        }

        return response()->json([
            'ok' => $response->successful,
            'provider' => $provider->name(),
            'model' => $response->model,
            'error_code' => $response->errorCode,
            'error_message' => $response->errorMessage,
        ]);
    }

    public function previewPrompt(Request $request): JsonResponse
    {
        $useCaseValue = (string) $request->query('use_case', AIUseCase::DateNightPlan->value);
        $useCase = AIUseCase::tryFrom($useCaseValue) ?? AIUseCase::DateNightPlan;

        $template = AiPromptTemplate::query()
            ->where('name', $useCase->value)
            ->where('active', true)
            ->orderByDesc('version')
            ->first();

        return response()->json([
            'use_case' => $useCase->value,
            'system_prompt' => $template?->system_prompt,
            'user_prompt_template' => $template?->user_prompt_template,
            'version' => $template?->version,
        ]);
    }

    public function previewResponse(Request $request, AIService $ai): JsonResponse
    {
        $useCaseValue = (string) $request->input('use_case', AIUseCase::DateNightPlan->value);
        $useCase = AIUseCase::tryFrom($useCaseValue) ?? AIUseCase::DateNightPlan;

        /** @var array<string, mixed> $context */
        $context = (array) $request->input('context', []);

        try {
            $response = $ai->generate($useCase, $context);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 200);
        }

        return response()->json([
            'ok' => $response->successful,
            'data' => $response->data,
            'model' => $response->model,
            'usage' => $response->usage,
            'error_code' => $response->errorCode,
            'error_message' => $response->errorMessage,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsSnapshot(): array
    {
        $base = AiGeneration::query();

        return [
            'total' => (clone $base)->count(),
            'successful' => (clone $base)->where('successful', true)->count(),
            'fallbacks' => (clone $base)->where('fallback_used', true)->count(),
            'total_tokens' => (int) (clone $base)->sum('total_tokens'),
            'avg_duration_ms' => (int) (clone $base)->avg('duration_ms'),
            'by_feature' => (clone $base)
                ->selectRaw('feature, COUNT(*) as generations, SUM(total_tokens) as tokens')
                ->groupBy('feature')
                ->get()
                ->toArray(),
        ];
    }
}
