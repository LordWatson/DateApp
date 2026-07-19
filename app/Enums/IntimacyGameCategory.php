<?php

namespace App\Enums;

enum IntimacyGameCategory: string
{
    case Game = 'game';
    case Dare = 'dare';
    case Roleplay = 'roleplay';
    case Truth = 'truth';

    public function label(): string
    {
        return match ($this) {
            self::Game => 'Game',
            self::Dare => 'Dares',
            self::Roleplay => 'Roleplay',
            self::Truth => 'Truth',
        };
    }
}
