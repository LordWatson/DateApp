<?php

namespace Tests\Feature;

use App\Enums\CompletionStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_can_be_created(): void
    {
        $user = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();

        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $this->assertEquals($user->id, $response->user_id);
        $this->assertEquals($questionnaire->id, $response->questionnaire_id);
        $this->assertEquals(CompletionStatus::InProgress, $response->status);
    }

    public function test_only_one_response_per_user_per_questionnaire(): void
    {
        $user = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();

        Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        $this->expectException(QueryException::class);

        Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);
    }

    public function test_response_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $response = Response::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $response->user->id);
    }

    public function test_response_belongs_to_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create(['questionnaire_id' => $questionnaire->id]);

        $this->assertEquals($questionnaire->id, $response->questionnaire->id);
    }

    public function test_response_can_be_completed(): void
    {
        $response = Response::factory()->create();

        $response->update([
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
            'compatibility_score' => 85,
        ]);

        $this->assertEquals(CompletionStatus::Completed, $response->fresh()->status);
        $this->assertEquals(85, $response->fresh()->compatibility_score);
        $this->assertNotNull($response->fresh()->completed_at);
    }

    public function test_answer_can_be_created_for_response(): void
    {
        $response = Response::factory()->create();
        $question = Question::factory()->singleChoice()->create([
            'questionnaire_id' => $response->questionnaire_id,
        ]);
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $answer = Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'value' => null,
        ]);

        $this->assertEquals($response->id, $answer->response_id);
        $this->assertEquals($question->id, $answer->question_id);
        $this->assertEquals($option->id, $answer->question_option_id);
    }

    public function test_response_has_many_answers(): void
    {
        $response = Response::factory()->create();
        Answer::factory()->count(3)->create(['response_id' => $response->id]);

        $this->assertCount(3, $response->answers);
    }

    public function test_answers_are_deleted_when_response_is_deleted(): void
    {
        $response = Response::factory()->create();
        Answer::factory()->count(3)->create(['response_id' => $response->id]);

        $response->delete();

        $this->assertEquals(0, Answer::count());
    }

    public function test_text_answer_stores_value(): void
    {
        $response = Response::factory()->create();
        $question = Question::factory()->text()->create([
            'questionnaire_id' => $response->questionnaire_id,
        ]);

        $answer = Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'question_option_id' => null,
            'value' => 'A glass of wine and soft music.',
        ]);

        $this->assertEquals('A glass of wine and soft music.', $answer->value);
        $this->assertNull($answer->question_option_id);
    }
}
