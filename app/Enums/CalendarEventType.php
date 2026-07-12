<?php

namespace App\Enums;

enum CalendarEventType: string
{
    case DateNight = 'date_night';
    case Anniversary = 'anniversary';
    case Birthday = 'birthday';
    case Holiday = 'holiday';
    case WeekendAway = 'weekend_away';
    case MovieNight = 'movie_night';
    case DinnerReservation = 'dinner_reservation';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::DateNight => 'Date Night',
            self::Anniversary => 'Anniversary',
            self::Birthday => 'Birthday',
            self::Holiday => 'Holiday',
            self::WeekendAway => 'Weekend Away',
            self::MovieNight => 'Movie Night',
            self::DinnerReservation => 'Dinner Reservation',
            self::Custom => 'Custom Event',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::DateNight => '💕',
            self::Anniversary => '💍',
            self::Birthday => '🎂',
            self::Holiday => '✈️',
            self::WeekendAway => '🏨',
            self::MovieNight => '🎬',
            self::DinnerReservation => '🍽️',
            self::Custom => '📅',
        };
    }
}
