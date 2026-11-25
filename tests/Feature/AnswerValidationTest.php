<?php

namespace Tests\Feature;

use App\Enums\CompletionStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AnswerValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Questionnaire $questionnaire;

    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->questionnaire = Questionnaire::factory()->create();
        $this->response = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $optionValues
     */
    private function question(string $state, array $attributes = [], array $optionValues = []): Question
    {
        $question = Question::factory()->{$state}()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'display_order' => Question::where('questionnaire_id', $this->questionnaire->id)->count() + 1,
            ...$attributes,
        ]);

        foreach ($optionValues as $value) {
            QuestionOption::factory()->create(['question_id' => $question->id, 'value' => $value]);
        }

        return $question;
    }

    private function answer(Question $question, mixed $value): TestResponse
    {
        return $this->actingAs($this->user)->postJson(
            route('questionnaires.answer', [$this->questionnaire->slug, $question->display_order]),
            ['value' => $value],
        );
    }

    public function test_single_choice_accepts_an_option_value_and_rejects_anything_else(): void
    {
        $question = $this->question('singleChoice', optionValues: ['free', 'treat']);

        $this->answer($question, 'treat')->assertOk();
        $this->answer($question, 'luxury')->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->answer($question, ['free'])->assertUnprocessable();
        $this->answer($question, 'custom_amount:50')->assertUnprocessable();

        $this->assertSame(['treat'], $this->response->answers()->pluck('value')->all());
    }

    public function test_single_choice_accepts_a_custom_amount_only_when_the_option_exists(): void
    {
        $question = $this->question('singleChoice', optionValues: ['free', 'custom_amount']);

        $this->answer($question, 'custom_amount:80')->assertOk();
        $this->answer($question, 'custom_amount')->assertOk();
        $this->answer($question, 'custom_amount:lots')->assertUnprocessable();
        $this->answer($question, 'custom_amount:-5')->assertUnprocessable();
    }

    public function test_multiple_choice_accepts_option_values_and_rejects_unknown_ones(): void
    {
        $question = $this->question('multipleChoice', optionValues: ['food', 'music', 'outdoors']);

        $this->answer($question, ['food', 'music'])->assertOk();
        $this->assertSame(2, $this->response->answers()->count());

        $this->answer($question, ['food', 'skydiving'])->assertUnprocessable();
        $this->answer($question, 'food')->assertUnprocessable();
        $this->assertSame(2, $this->response->answers()->count());

        // Deselecting everything clears the answer.
        $this->answer($question, [])->assertOk();
        $this->assertSame(0, $this->response->answers()->count());
    }

    public function test_slider_accepts_values_on_a_step_within_the_range(): void
    {
        $question = $this->question('slider', ['minimum_value' => 0, 'maximum_value' => 240, 'step_value' => 30]);

        $this->answer($question, 90)->assertOk();
        $this->answer($question, '240')->assertOk();
        $this->answer($question, 100)->assertUnprocessable();
        $this->answer($question, 270)->assertUnprocessable();
        $this->answer($question, -30)->assertUnprocessable();
        $this->answer($question, 'far')->assertUnprocessable();
    }

    public function test_slider_without_bounds_uses_one_to_ten(): void
    {
        $question = $this->question('slider', ['minimum_value' => null, 'maximum_value' => null]);

        $this->answer($question, 1)->assertOk();
        $this->answer($question, 10)->assertOk();
        $this->answer($question, 0)->assertUnprocessable();
        $this->answer($question, 11)->assertUnprocessable();
        $this->answer($question, 5.5)->assertUnprocessable();
    }

    public function test_text_and_textarea_accept_strings_up_to_their_limit(): void
    {
        $text = $this->question('text');
        $textarea = $this->question('textarea');

        $this->answer($text, str_repeat('a', 255))->assertOk();
        $this->answer($text, str_repeat('a', 256))->assertUnprocessable();
        $this->answer($text, ['a'])->assertUnprocessable();

        $this->answer($textarea, str_repeat('a', 500))->assertOk();
        $this->answer($textarea, str_repeat('a', 501))->assertUnprocessable();
    }

    public function test_finish_refuses_while_a_required_question_is_unanswered(): void
    {
        $required = $this->question('singleChoice', ['required' => true], ['yes']);
        $this->question('textarea', ['required' => false]);

        $this->actingAs($this->user)
            ->post(route('questionnaires.finish', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.summary', $this->questionnaire->slug))
            ->assertSessionHasErrors(['answers' => 'Please answer every required question before completing: Q1.']);

        $this->assertSame(CompletionStatus::InProgress, $this->response->fresh()->status);

        // A cleared (empty) answer does not count either.
        Answer::factory()->create(['response_id' => $this->response->id, 'question_id' => $required->id, 'value' => null]);

        $this->actingAs($this->user)
            ->post(route('questionnaires.finish', $this->questionnaire->slug))
            ->assertSessionHasErrors('answers');

        $this->actingAs($this->user)
            ->get(route('questionnaires.summary', $this->questionnaire->slug))
            ->assertOk();
    }

    public function test_finish_completes_once_every_required_question_is_answered(): void
    {
        $required = $this->question('singleChoice', ['required' => true], ['yes']);
        $this->question('textarea', ['required' => false]);

        $this->answer($required, 'yes')->assertOk();

        $this->actingAs($this->user)
            ->post(route('questionnaires.finish', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.complete', $this->questionnaire->slug))
            ->assertSessionHasNoErrors();

        $this->assertSame(CompletionStatus::Completed, $this->response->fresh()->status);
    }
}
