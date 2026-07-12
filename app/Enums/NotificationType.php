<?php

namespace App\Enums;

enum NotificationType: string
{
    case PartnerCompleted = 'partner_completed';
    case CompatibilityReady = 'compatibility_ready';
    case DailyChallenge = 'daily_challenge';
}
