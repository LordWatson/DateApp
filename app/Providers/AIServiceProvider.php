<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AI\AIProvider;
use App\Services\AI\DeepSeekProvider;
use App\Services\AI\NullAIProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, class-string<AIProvider>>
     */
    private const PROVIDERS = [
        'deepseek' => DeepSeekProvider::class,
        'null' => NullAIProvider::class,
    ];

    public function register(): void
    {
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
}
