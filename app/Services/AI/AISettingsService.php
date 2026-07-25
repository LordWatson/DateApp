<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\SystemSetting;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Persistence gateway for administrator-controlled AI settings.
 *
 * Backed by the existing `system_settings` table so admins can edit
 * runtime AI knobs without redeploys. On boot, the config repository
 * is overlaid with any stored values.
 */
final class AISettingsService
{
    private const KEYS = [
        'provider' => 'ai.provider',
        'model' => 'ai.providers.deepseek.model',
        'temperature' => 'ai.defaults.temperature',
        'max_tokens' => 'ai.defaults.max_tokens',
        'timeout' => 'ai.defaults.timeout',
        'retry_attempts' => 'ai.defaults.retry_attempts',
    ];

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return [
            'provider' => (string) $this->config->get('ai.provider'),
            'model' => (string) $this->config->get('ai.providers.deepseek.model'),
            'temperature' => (float) $this->config->get('ai.defaults.temperature'),
            'max_tokens' => (int) $this->config->get('ai.defaults.max_tokens'),
            'timeout' => (int) $this->config->get('ai.defaults.timeout'),
            'retry_attempts' => (int) $this->config->get('ai.defaults.retry_attempts'),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach (self::KEYS as $field => $configPath) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $stringValue = is_bool($values[$field])
                ? ($values[$field] ? '1' : '0')
                : (string) $values[$field];

            SystemSetting::updateOrCreate(
                ['key' => 'ai.'.$field],
                [
                    'label' => 'AI '.ucfirst(str_replace('_', ' ', $field)),
                    'group' => 'ai',
                    'type' => is_int($values[$field]) ? 'integer' : 'string',
                    'value' => $stringValue,
                ],
            );

            $this->config->set($configPath, $values[$field]);
        }
    }

    /**
     * Hydrate the config repository from persisted settings.
     * Should be invoked from a service provider `boot()`.
     */
    public function hydrateConfig(): void
    {
        try {
            $rows = SystemSetting::query()
                ->where('key', 'like', 'ai.%')
                ->get(['key', 'value']);
        } catch (\Throwable) {
            // Table may not exist yet during migrations; ignore.
            return;
        }

        foreach ($rows as $row) {
            $short = substr($row->key, 3); // strip "ai." prefix
            if (! isset(self::KEYS[$short]) || $row->value === null) {
                continue;
            }

            $this->config->set(self::KEYS[$short], $this->cast($short, $row->value));
        }
    }

    private function cast(string $field, string $value): float|int|string
    {
        return match ($field) {
            'temperature' => (float) $value,
            'max_tokens', 'timeout', 'retry_attempts' => (int) $value,
            default => $value,
        };
    }
}
