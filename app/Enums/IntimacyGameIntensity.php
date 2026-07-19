<?php

namespace App\Enums;

enum IntimacyGameIntensity: string
{
    case Flirty = 'flirty';
    case Spicy = 'spicy';
    case Wild = 'wild';

    public function label(): string
    {
        return match ($this) {
            self::Flirty => 'Flirty',
            self::Spicy => 'Spicy',
            self::Wild => 'Wild',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Flirty => '💋',
            self::Spicy => '🌶️',
            self::Wild => '🔥',
        };
    }
}
