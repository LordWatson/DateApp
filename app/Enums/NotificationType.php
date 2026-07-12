<?php

namespace App\Enums;

enum NotificationType: string
{
    case PartnerCompleted = 'partner_completed';
    case CompatibilityReady = 'compatibility_ready';
    case DailyChallenge = 'daily_challenge';
    case DateNightPlanReady = 'date_night_plan_ready';
    case LoveNoteReceived = 'love_note_received';
    case PartnerViewedPlan = 'partner_viewed_plan';
}
