<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Challenge;
use App\Models\DailyChallenge;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertIsInt($user->id);
        $this->assertIsString($user->name);
        $this->assertIsString($user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_questionnaire_factory_creates_valid_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->create();

        $this->assertIsInt($questionnaire->id);
        $this->assertIsString($questionnaire->title);
        $this->assertIsString($questionnaire->slug);
    }

    public function test_question_factory_creates_valid_question(): void
    {
        $question = Question::factory()->create();

        $this->assertIsInt($question->id);
        $this->assertIsString($question->title);
        $this->assertIsInt($question->questionnaire_id);
    }

    public function test_question_option_factory_creates_valid_option(): void
    {
        $option = QuestionOption::factory()->create();

        $this->assertIsInt($option->id);
        $this->assertIsString($option->title);
        $this->assertIsString($option->value);
    }

    public function test_response_factory_creates_valid_response(): void
    {
        $response = Response::factory()->create();

        $this->assertIsInt($response->id);
        $this->assertIsInt($response->user_id);
        $this->assertIsInt($response->questionnaire_id);
    }

    public function test_response_factory_completed_state(): void
    {
        $response = Response::factory()->completed()->create();

        $this->assertNotNull($response->completed_at);
        $this->assertIsInt($response->compatibility_score);
    }

    public function test_answer_factory_creates_valid_answer(): void
    {
        $answer = Answer::factory()->create();

        $this->assertIsInt($answer->id);
        $this->assertIsInt($answer->response_id);
        $this->assertIsInt($answer->question_id);
    }

    public function test_saved_profile_factory_creates_valid_profile(): void
    {
        $profile = SavedProfile::factory()->create();

        $this->assertIsInt($profile->id);
        $this->assertIsString($profile->name);
        $this->assertIsInt($profile->user_id);
    }

    public function test_saved_profile_answer_factory_creates_valid_answer(): void
    {
        $answer = SavedProfileAnswer::factory()->create();

        $this->assertIsInt($answer->id);
        $this->assertIsInt($answer->saved_profile_id);
        $this->assertIsInt($answer->question_id);
    }

    public function test_challenge_factory_creates_valid_challenge(): void
    {
        $challenge = Challenge::factory()->create();

        $this->assertIsInt($challenge->id);
        $this->assertIsString($challenge->title);
        $this->assertTrue($challenge->active);
    }

    public function test_daily_challenge_factory_creates_valid_daily_challenge(): void
    {
        $daily = DailyChallenge::factory()->create();

        $this->assertIsInt($daily->id);
        $this->assertIsInt($daily->challenge_id);
        $this->assertNotNull($daily->date);
    }

    public function test_user_factory_with_streak_state(): void
    {
        $user = User::factory()->withStreak(7)->create();

        $this->assertEquals(7, $user->current_streak);
        $this->assertGreaterThanOrEqual(7, $user->longest_streak);
        $this->assertNotNull($user->last_completed_questionnaire_at);
    }

    public function test_challenge_factory_difficulty_states(): void
    {
        $easy = Challenge::factory()->easy()->create();
        $medium = Challenge::factory()->medium()->create();
        $hard = Challenge::factory()->hard()->create();

        $this->assertEquals('easy', $easy->difficulty->value);
        $this->assertEquals('medium', $medium->difficulty->value);
        $this->assertEquals('hard', $hard->difficulty->value);
    }
}
