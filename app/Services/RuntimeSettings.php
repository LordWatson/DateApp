<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Container\Attributes\Scoped;
use Throwable;

/**
 * Reads the admin-managed feature flags and system settings that the app acts on.
 *
 * Each table is read once per request or queued job (the instance is scoped). A missing
 * row or table falls back to the default, so migrations and unseeded tests behave like a
 * fresh install.
 */
#[Scoped]
class RuntimeSettings
{
    /**
     * Flags the app reads, with the value used when there is no row. Everything else in
     * `feature_flags` is stored but not in use.
     */
    public const FLAG_DEFAULTS = [
        'registration' => true,
        'maintenance_mode' => false,
        'ai_generation' => true,
    ];

    /**
     * Setting keys the app reads. Everything else in `system_settings` is stored but not
     * in use (the `ai.*` keys are handled by AISettingsService).
     */
    public const SETTINGS_IN_USE = ['invitation_expiry_days'];

    /** @var array<string, mixed>|null */
    private ?array $flags = null;

    /** @var array<string, mixed>|null */
    private ?array $settings = null;

    public function flag(string $key): bool
    {
        $this->flags ??= $this->load(fn (): array => FeatureFlag::query()->pluck('enabled', 'key')->all());

        return (bool) ($this->flags[$key] ?? self::FLAG_DEFAULTS[$key] ?? false);
    }

    public function integer(string $key, int $default): int
    {
        $this->settings ??= $this->load(fn (): array => SystemSetting::query()->pluck('value', 'key')->all());

        $value = $this->settings[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    public function invitationExpiryDays(): int
    {
        return max(1, $this->integer('invitation_expiry_days', 7));
    }

    /**
     * @param  Closure(): array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function load(Closure $query): array
    {
        try {
            return $query();
        } catch (Throwable) {
            // The table does not exist yet (first migration, a bare test database).
            return [];
        }
    }
}
