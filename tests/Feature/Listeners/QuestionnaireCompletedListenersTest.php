<?php

namespace Tests\Feature\Listeners;

use App\Enums\CompletionStatus;
use App\Events\QuestionnaireCompleted;
use App\Jobs\GenerateDateNightPlanJob;
use App\Jobs\SendQuestionnaireCompletedNotificationJob;
use App\Listeners\DispatchDateNightPlanOnQuestionnaireCompleted;
use App\Listeners\EvaluateAchievementsOnQuestionnaireCompleted;
use App\Listeners\NotifyPartnerOnQuestionnaireCompleted;
use App\Listeners\RefreshDashboardOnQuestionnaireCompleted;
use App\Listeners\UpdateStreakOnQuestionnairCompleted;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuestionnaireCompletedListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_completed_has_correct_listeners_registered(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        QuestionnaireCompleted::dispatch($user, $questionnaire, $response);

        Event::assertListening(QuestionnaireCompleted::class, UpdateStreakOnQuestionnairCompleted::class);
        Event::assertListening(QuestionnaireCompleted::class, EvaluateAchievementsOnQuestionnaireCompleted::class);
        Event::assertListening(QuestionnaireCompleted::class, RefreshDashboardOnQuestionnaireCompleted::class);
        Event::assertListening(QuestionnaireCompleted::class, DispatchDateNightPlanOnQuestionnaireCompleted::class);
    }

    public function test_dispatches_generate_date_night_plan_job_when_partner_exists(): void
    {
        Queue::fake();

        $partner = User::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $questionnaire = Questionnaire::factory()->create();
        Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);

        $listener = app(DispatchDateNightPlanOnQuestionnaireCompleted::class);
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

        Queue::assertPushed(GenerateDateNightPlanJob::class);
    }

    public function test_does_not_dispatch_job_when_partner_has_not_completed(): void
    {
        Queue::fake();

        $partner = User::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);

        $listener = app(DispatchDateNightPlanOnQuestionnaireCompleted::class);
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

        Queue::assertNotPushed(GenerateDateNightPlanJob::class);
    }

    public function test_does_not_dispatch_job_when_plan_already_exists_for_latest_responses(): void
    {
        Queue::fake();

        $partner = User::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $questionnaire = Questionnaire::factory()->create();

        $partnerResponse = Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);
        $userResponse = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);

        DateNightPlan::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => $userResponse->id,
            'partner_two_response_id' => $partnerResponse->id,
        ]);

        // Simulate the user restarting and completing again while partner has not restarted.
        $newUserResponse = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now()->addMinute(),
        ]);

        $listener = app(DispatchDateNightPlanOnQuestionnaireCompleted::class);
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $newUserResponse));

        // Partner's latest completed response is still the one already used in a plan,
        // so no new plan should be generated until the partner also restarts and completes.
        Queue::assertNotPushed(GenerateDateNightPlanJob::class);
    }

    public function test_dispatches_job_when_both_partners_restart_and_complete_again(): void
    {
        Queue::fake();

        $partner = User::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $questionnaire = Questionnaire::factory()->create();

        $oldPartnerResponse = Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);
        $oldUserResponse = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);

        DateNightPlan::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => $oldUserResponse->id,
            'partner_two_response_id' => $oldPartnerResponse->id,
        ]);

        // Both partners restart and complete a new response.
        Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);
        $newUserResponse = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);

        $listener = app(DispatchDateNightPlanOnQuestionnaireCompleted::class);
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $newUserResponse));

        Queue::assertPushed(GenerateDateNightPlanJob::class);
    }

    public function test_does_not_dispatch_job_when_no_partner(): void
    {
        Queue::fake();

        $user = User::factory()->create(['partner_id' => null]);
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $listener = app(DispatchDateNightPlanOnQuestionnaireCompleted::class);
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

        Queue::assertNotPushed(GenerateDateNightPlanJob::class);
    }

    public function test_notify_partner_creates_app_notification_and_dispatches_email_job(): void
    {
        Queue::fake();

        $partner = User::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $listener = new NotifyPartnerOnQuestionnaireCompleted;
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $partner->id,
            'type' => 'partner_completed',
        ]);

        $notification = AppNotification::where('user_id', $partner->id)->firstOrFail();
        $this->assertSame($questionnaire->id, $notification->data['questionnaire_id']);
        $this->assertSame($questionnaire->slug, $notification->data['questionnaire_slug']);

        Queue::assertPushed(SendQuestionnaireCompletedNotificationJob::class, function ($job) use ($partner, $user, $questionnaire) {
            return $job->recipient->id === $partner->id
                && $job->sender->id === $user->id
                && $job->questionnaire->id === $questionnaire->id;
        });
    }

    public function test_notify_partner_does_nothing_when_no_partner(): void
    {
        Queue::fake();

        $user = User::factory()->create(['partner_id' => null]);
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $listener = new NotifyPartnerOnQuestionnaireCompleted;
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

        $this->assertDatabaseCount('app_notifications', 0);
        Queue::assertNotPushed(SendQuestionnaireCompletedNotificationJob::class);
    }
}
