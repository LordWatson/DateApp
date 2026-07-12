<?php

namespace App\Enums;

enum AchievementCategory: string
{
    case Streaks = 'streaks';
    case Questionnaires = 'questionnaires';
    case LoveNotes = 'love_notes';
    case Calendar = 'calendar';
    case Moments = 'moments';
    case Compatibility = 'compatibility';
    case Romance = 'romance';
    case Milestones = 'milestones';

    public function label(): string
    {
        return match ($this) {
            self::Streaks => 'Streaks',
            self::Questionnaires => 'Questionnaires',
            self::LoveNotes => 'Love Notes',
            self::Calendar => 'Calendar',
            self::Moments => 'Moments',
            self::Compatibility => 'Compatibility',
            self::Romance => 'Romance',
            self::Milestones => 'Milestones',
        };
    }
}
