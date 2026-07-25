<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AI\AIProvider;
use App\Services\AI\AISettingsService;
use App\Services\AI\DeepSeekProvider;
use App\Services\AI\NullAIProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Registry mapping the config `ai.provider` value to a concrete
     * AIProvider implementation. Adding a new vendor (OpenAI, Anthropic,
     * Gemini, ...) is a one-line change here — no application code needs
     * to be touched.
     *
     * @var array<string, class-string<AIProvider>>
     */
    private const PROVIDERS = [
        'deepseek' => DeepSeekProvider::class,
        'null' => NullAIProvider::class,
    ];

    public function register(): void
    {
        $this->app->singleton(AISettingsService::class);

        $this->app->singleton(AIProvider::class, function (Application $app): AIProvider {
            $provider = (string) $app['config']->get('ai.provider', 'deepseek');

            if (! isset(self::PROVIDERS[$provider])) {
                throw new BindingResolutionException(
                    "Unsupported AI provider [{$provider}]. Configure AI_PROVIDER to one of: "
                    .implode(', ', array_keys(self::PROVIDERS)).'.'
                );
            }

            return $app->make(self::PROVIDERS[$provider]);
        });
    }

    public function boot(AISettingsService $settings): void
    {
        // Overlay any admin-persisted AI settings on top of the config
        // defaults so the app respects runtime configuration changes
        // without a redeploy.
        $settings->hydrateConfig();
    }
}
