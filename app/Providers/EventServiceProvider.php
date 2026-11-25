<?php

namespace App\Providers;

use App\Events\AchievementUnlocked;
use App\Events\CalendarEventCreated;
use App\Events\ChallengeCompleted;
use App\Events\DateNightPlanGenerated;
use App\Events\LoveNoteSent;
use App\Events\MomentCreated;
use App\Events\PartnerConnected;
use App\Events\PartnerDisconnected;
use App\Events\PartnerInvitationAccepted;
use App\Events\QuestionnaireCompleted;
use App\Listeners\CreatePartnerConnectedNotificationOnPartnerConnected;
use App\Listeners\DeleteDeviceTokensOnLogout;
use App\Listeners\DispatchDateNightPlanOnQuestionnaireCompleted;
use App\Listeners\EvaluateAchievementsOnActivity;
use App\Listeners\EvaluateAchievementsOnMomentCreated;
use App\Listeners\EvaluateAchievementsOnQuestionnaireCompleted;
use App\Listeners\LogActivityOnAchievementUnlocked;
use App\Listeners\LogActivityOnLoveNoteSent;
use App\Listeners\LogActivityOnMomentCreated;
use App\Listeners\LogActivityOnPartnerConnected;
use App\Listeners\LogActivityOnQuestionnaireCompleted;
use App\Listeners\NotifyFormerPartnerOnPartnerDisconnected;
use App\Listeners\NotifyOnAchievementUnlocked;
use App\Listeners\NotifyPartnerOnChallengeCompleted;
use App\Listeners\NotifyPartnerOnQuestionnaireCompleted;
use App\Listeners\NotifyPlanReadyOnDateNightPlanGenerated;
use App\Listeners\SendLoveNoteNotificationOnLoveNoteSent;
use App\Listeners\SendMomentNotificationOnMomentCreated;
use App\Listeners\SendPartnerConnectedEmailsOnPartnerConnected;
use App\Listeners\SendPlanEmailsOnDateNightPlanGenerated;
use App\Listeners\UpdateStreakOnQuestionnairCompleted;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Logout::class => [
            DeleteDeviceTokensOnLogout::class,
        ],

        PartnerInvitationAccepted::class => [],

        PartnerConnected::class => [
            SendPartnerConnectedEmailsOnPartnerConnected::class,
            CreatePartnerConnectedNotificationOnPartnerConnected::class,
            LogActivityOnPartnerConnected::class,
        ],

        PartnerDisconnected::class => [
            NotifyFormerPartnerOnPartnerDisconnected::class,
        ],

        QuestionnaireCompleted::class => [
            UpdateStreakOnQuestionnairCompleted::class,
            NotifyPartnerOnQuestionnaireCompleted::class,
            DispatchDateNightPlanOnQuestionnaireCompleted::class,
            EvaluateAchievementsOnQuestionnaireCompleted::class,
            LogActivityOnQuestionnaireCompleted::class,
        ],

        DateNightPlanGenerated::class => [
            SendPlanEmailsOnDateNightPlanGenerated::class,
            NotifyPlanReadyOnDateNightPlanGenerated::class,
        ],

        LoveNoteSent::class => [
            SendLoveNoteNotificationOnLoveNoteSent::class,
            LogActivityOnLoveNoteSent::class,
            EvaluateAchievementsOnActivity::class,
        ],

        MomentCreated::class => [
            SendMomentNotificationOnMomentCreated::class,
            LogActivityOnMomentCreated::class,
            EvaluateAchievementsOnMomentCreated::class,
        ],

        CalendarEventCreated::class => [
            EvaluateAchievementsOnActivity::class,
        ],

        ChallengeCompleted::class => [
            NotifyPartnerOnChallengeCompleted::class,
            EvaluateAchievementsOnActivity::class,
        ],

        AchievementUnlocked::class => [
            NotifyOnAchievementUnlocked::class,
            LogActivityOnAchievementUnlocked::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
