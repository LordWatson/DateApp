<?php

namespace Tests\Feature\Listeners;

use App\Events\QuestionnaireCompleted;
use App\Jobs\GenerateDateNightPlanJob;
use App\Listeners\DispatchDateNightPlanOnQuestionnaireCompleted;
use App\Listeners\EvaluateAchievementsOnQuestionnaireCompleted;
use App\Listeners\RefreshDashboardOnQuestionnaireCompleted;
use App\Listeners\UpdateStreakOnQuestionnairCompleted;
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
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $listener = new DispatchDateNightPlanOnQuestionnaireCompleted;
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

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

        $listener = new DispatchDateNightPlanOnQuestionnaireCompleted;
        $listener->handle(new QuestionnaireCompleted($user, $questionnaire, $response));

        Queue::assertNotPushed(GenerateDateNightPlanJob::class);
    }
}
