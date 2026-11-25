<?php

namespace App\Enums;

enum NotificationType: string
{
    case PartnerCompleted = 'partner_completed';
    case CompatibilityReady = 'compatibility_ready';
    case DailyChallenge = 'daily_challenge';
    case DateNightPlanReady = 'date_night_plan_ready';
    case LoveNoteReceived = 'love_note_received';
    case MomentCreated = 'moment_created';
    case PartnerViewedPlan = 'partner_viewed_plan';
    case PartnerLikedPlan = 'partner_liked_plan';
    case PartnerAddedDateNightToCalendar = 'partner_added_date_night_to_calendar';
    case PartnerLeft = 'partner_left';
    case PartnerConnected = 'partner_connected';
    case AchievementUnlocked = 'achievement_unlocked';
    case QuestionnaireReminder = 'questionnaire_reminder';
    case PlanFailed = 'plan_failed';
    case InvitationDeclined = 'invitation_declined';
    case CalendarReminder = 'calendar_reminder';
    case InvitationExpired = 'invitation_expired';
    case DataExportReady = 'data_export_ready';
    case PartnerNudge = 'partner_nudge';
    case LoveNoteReaction = 'love_note_reaction';
    case ConnectionAnniversary = 'connection_anniversary';
    case PartnerCompletedPlan = 'partner_completed_plan';
    case ConnectionPaused = 'connection_paused';
    case ConnectionResumed = 'connection_resumed';
}
