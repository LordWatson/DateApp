<?php

namespace App\Providers;

use App\Events\AchievementUnlocked;
use App\Events\CalendarEventCreated;
use App\Events\CompatibilityCalculated;
use App\Events\DateNightPlanGenerated;
use App\Events\ExportRequested;
use App\Events\LoveNoteSent;
use App\Events\MomentCreated;
use App\Events\PartnerConnected;
use App\Events\PartnerDisconnected;
use App\Events\PartnerInvitationAccepted;
use App\Events\PartnerInvitationCreated;
use App\Events\ProfileCompleted;
use App\Events\QuestionAnswered;
use App\Events\QuestionnaireCompleted;
use App\Events\QuestionnaireStarted;
use App\Events\SavedProfileApplied;
use App\Events\SavedProfileCreated;
use App\Events\UserRegistered;
use App\Listeners\CreatePartnerConnectedNotificationOnPartnerConnected;
use App\Listeners\DispatchDateNightPlanOnQuestionnaireCompleted;
use App\Listeners\EvaluateAchievementsOnMomentCreated;
use App\Listeners\EvaluateAchievementsOnQuestionnaireCompleted;
use App\Listeners\InvalidateCacheOnPartnerConnected;
use App\Listeners\InvalidateCacheOnQuestionnaireCompleted;
use App\Listeners\LogActivityOnAchievementUnlocked;
use App\Listeners\LogActivityOnLoveNoteSent;
use App\Listeners\LogActivityOnMomentCreated;
use App\Listeners\LogActivityOnPartnerConnected;
use App\Listeners\LogActivityOnQuestionnaireCompleted;
use App\Listeners\NotifyOnAchievementUnlocked;
use App\Listeners\NotifyPartnerOnQuestionnaireCompleted;
use App\Listeners\NotifyPlanReadyOnDateNightPlanGenerated;
use App\Listeners\RefreshDashboardOnPartnerConnected;
use App\Listeners\RefreshDashboardOnQuestionnaireCompleted;
use App\Listeners\SendInvitationEmailOnPartnerInvitationCreated;
use App\Listeners\SendLoveNoteNotificationOnLoveNoteSent;
use App\Listeners\SendPartnerConnectedEmailsOnPartnerConnected;
use App\Listeners\SendPlanEmailsOnDateNightPlanGenerated;
use App\Listeners\UpdateStreakOnQuestionnairCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [],

        ProfileCompleted::class => [],

        PartnerInvitationCreated::class => [
            SendInvitationEmailOnPartnerInvitationCreated::class,
        ],

        PartnerInvitationAccepted::class => [],

        PartnerConnected::class => [
            SendPartnerConnectedEmailsOnPartnerConnected::class,
            CreatePartnerConnectedNotificationOnPartnerConnected::class,
            RefreshDashboardOnPartnerConnected::class,
            LogActivityOnPartnerConnected::class,
            InvalidateCacheOnPartnerConnected::class,
        ],

        PartnerDisconnected::class => [],

        QuestionnaireStarted::class => [],

        QuestionAnswered::class => [],

        QuestionnaireCompleted::class => [
            UpdateStreakOnQuestionnairCompleted::class,
            NotifyPartnerOnQuestionnaireCompleted::class,
            DispatchDateNightPlanOnQuestionnaireCompleted::class,
            EvaluateAchievementsOnQuestionnaireCompleted::class,
            RefreshDashboardOnQuestionnaireCompleted::class,
            LogActivityOnQuestionnaireCompleted::class,
            InvalidateCacheOnQuestionnaireCompleted::class,
        ],

        CompatibilityCalculated::class => [],

        DateNightPlanGenerated::class => [
            SendPlanEmailsOnDateNightPlanGenerated::class,
            NotifyPlanReadyOnDateNightPlanGenerated::class,
        ],

        LoveNoteSent::class => [
            SendLoveNoteNotificationOnLoveNoteSent::class,
            LogActivityOnLoveNoteSent::class,
        ],

        MomentCreated::class => [
            LogActivityOnMomentCreated::class,
            EvaluateAchievementsOnMomentCreated::class,
        ],

        CalendarEventCreated::class => [],

        AchievementUnlocked::class => [
            NotifyOnAchievementUnlocked::class,
            LogActivityOnAchievementUnlocked::class,
        ],

        SavedProfileCreated::class => [],

        SavedProfileApplied::class => [],

        ExportRequested::class => [],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
