<?php

namespace App\Enums;

/**
 * The kinds of notification email a user can switch off one by one.
 * Only kinds that actually send an email are listed.
 */
enum NotificationCategory: string
{
    case PartnerActivity = 'partner_activity';
    case LoveNotes = 'love_notes';
    case Moments = 'moments';

    public function label(): string
    {
        return match ($this) {
            self::PartnerActivity => 'Partner activity',
            self::LoveNotes => 'Love notes',
            self::Moments => 'Moments',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PartnerActivity => 'Your partner is waiting for your answers, a date night plan is ready, or you have connected.',
            self::LoveNotes => 'Your partner sent you a love note.',
            self::Moments => 'Your partner saved a new moment.',
        };
    }
}
