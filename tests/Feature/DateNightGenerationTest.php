<?php

namespace Tests\Feature;

use App\Enums\CompletionStatus;
use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Mail\DateNightReadyMail;
use App\Models\DateNightPlan;
use App\Models\DateNightTheme;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\DateNightGeneratorService;
use App\Services\QuestionnaireService;
use App\Services\RuleEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DateNightGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $userOne;

    private User $userTwo;

    private Questionnaire $questionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userOne = User::factory()->create(['onboarding_completed' => true]);
        $this->userTwo = User::factory()->create(['onboarding_completed' => true]);

        $this->userOne->update(['partner_id' => $this->userTwo->id]);
        $this->userTwo->update(['partner_id' => $this->userOne->id]);

        $this->questionnaire = Questionnaire::factory()->create([
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
        ]);
    }

    public function test_plan_is_generated_when_both_partners_complete_questionnaire(): void
    {
        Mail::fake();

        DateNightTheme::factory()->create(['name' => 'Cozy Night In', 'emoji' => '❤️', 'active' => true]);

        Response::factory()->completed()->create([
            'user_id' => $this->userOne->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $responseTwo = Response::factory()->create([
            'user_id' => $this->userTwo->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $service = app(QuestionnaireService::class);
        $service->completeResponse($responseTwo);

        $this->assertDatabaseHas('date_night_plans', [
            'questionnaire_id' => $this->questionnaire->id,
        ]);
    }

    public function test_emails_are_queued_when_plan_is_generated(): void
    {
        Mail::fake();

        DateNightTheme::factory()->create(['name' => 'Cozy Night In', 'emoji' => '❤️', 'active' => true]);

        $responseOne = Response::factory()->completed()->create([
            'user_id' => $this->userOne->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $responseTwo = Response::factory()->create([
            'user_id' => $this->userTwo->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $service = app(QuestionnaireService::class);
        $service->completeResponse($responseTwo);

        Mail::assertQueued(DateNightReadyMail::class);
    }

    public function test_plan_is_not_duplicated_if_already_exists(): void
    {
        Mail::fake();

        DateNightTheme::factory()->create(['name' => 'Cozy Night In', 'emoji' => '❤️', 'active' => true]);

        $responseOne = Response::factory()->completed()->create([
            'user_id' => $this->userOne->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $responseTwo = Response::factory()->completed()->create([
            'user_id' => $this->userTwo->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        DateNightPlan::factory()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'partner_one_response_id' => $responseOne->id,
            'partner_two_response_id' => $responseTwo->id,
        ]);

        $service = app(QuestionnaireService::class);
        $service->completeResponse($responseTwo->fresh());

        $this->assertDatabaseCount('date_night_plans', 1);
    }

    public function test_generator_service_produces_all_required_fields(): void
    {
        $theme = DateNightTheme::factory()->create([
            'name' => 'Cozy Night In',
            'emoji' => '❤️',
            'description' => 'a warm evening at home',
            'active' => true,
        ]);

        $responseOne = Response::factory()->completed()->create([
            'user_id' => $this->userOne->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $responseTwo = Response::factory()->completed()->create([
            'user_id' => $this->userTwo->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $generator = app(DateNightGeneratorService::class);
        $plan = $generator->generate(
            $responseOne,
            $responseTwo,
            ['percentage' => 75, 'matched' => [], 'different' => []],
            $this->questionnaire,
        );

        $this->assertNotNull($plan->theme);
        $this->assertNotNull($plan->summary);
        $this->assertNotNull($plan->meal_suggestion);
        $this->assertNotNull($plan->atmosphere);
        $this->assertNotNull($plan->activity);
        $this->assertNotNull($plan->conversation_prompt);
        $this->assertNotNull($plan->romantic_challenge);
        $this->assertEquals(75, $plan->compatibility_score);
    }

    public function test_rule_engine_selects_theme_based_on_answers(): void
    {
        $ruleEngine = app(RuleEngineService::class);

        $themes = collect([
            DateNightTheme::factory()->make(['name' => 'Movie Marathon', 'id' => 1]),
            DateNightTheme::factory()->make(['name' => 'Cozy Night In', 'id' => 2]),
        ]);

        $answers = collect([
            (object) ['value' => 'movie'],
            (object) ['value' => 'netflix'],
        ]);

        $selected = $ruleEngine->selectTheme($answers, $answers, 60, $themes);

        $this->assertEquals('Movie Marathon', $selected->name);
    }

    public function test_rule_engine_derives_context_correctly(): void
    {
        $ruleEngine = app(RuleEngineService::class);

        $answers = collect([
            (object) ['value' => 'music'],
            (object) ['value' => 'dance'],
        ]);

        $context = $ruleEngine->deriveContext($answers, $answers, 70);

        $this->assertTrue($context['prefers_music']);
        $this->assertFalse($context['prefers_movies']);
    }

    public function test_notifications_are_created_when_plan_is_generated(): void
    {
        Mail::fake();

        DateNightTheme::factory()->create(['name' => 'Cozy Night In', 'emoji' => '❤️', 'active' => true]);

        Response::factory()->completed()->create([
            'user_id' => $this->userOne->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $responseTwo = Response::factory()->create([
            'user_id' => $this->userTwo->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $service = app(QuestionnaireService::class);
        $service->completeResponse($responseTwo);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->userOne->id,
            'type' => 'date_night_plan_ready',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->userTwo->id,
            'type' => 'date_night_plan_ready',
        ]);
    }
}
