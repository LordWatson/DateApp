<?php

namespace Tests\Feature;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_can_be_created(): void
    {
        $questionnaire = Questionnaire::factory()->create([
            'title' => '❤️ Tonight',
            'slug' => 'tonight',
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
        ]);

        $this->assertEquals('❤️ Tonight', $questionnaire->title);
        $this->assertEquals('tonight', $questionnaire->slug);
        $this->assertEquals(QuestionnaireStatus::Active, $questionnaire->status);
        $this->assertEquals(QuestionnaireVisibility::Public, $questionnaire->visibility);
    }

    public function test_questionnaire_has_many_questions(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        Question::factory()->count(5)->create(['questionnaire_id' => $questionnaire->id]);

        $this->assertCount(5, $questionnaire->questions);
    }

    public function test_questions_are_ordered_by_display_order(): void
    {
        $questionnaire = Questionnaire::factory()->create();

        Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'display_order' => 3]);
        Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'display_order' => 1]);
        Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'display_order' => 2]);

        $orders = $questionnaire->questions->pluck('display_order')->toArray();

        $this->assertEquals([1, 2, 3], $orders);
    }

    public function test_question_has_many_options(): void
    {
        $question = Question::factory()->singleChoice()->create();
        QuestionOption::factory()->count(3)->create(['question_id' => $question->id]);

        $this->assertCount(3, $question->options);
    }

    public function test_options_are_ordered_by_display_order(): void
    {
        $question = Question::factory()->singleChoice()->create();

        QuestionOption::factory()->create(['question_id' => $question->id, 'display_order' => 3]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'display_order' => 1]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'display_order' => 2]);

        $orders = $question->options->pluck('display_order')->toArray();

        $this->assertEquals([1, 2, 3], $orders);
    }

    public function test_slider_question_stores_min_max_values(): void
    {
        $question = Question::factory()->slider(1, 10)->create();

        $this->assertEquals(QuestionType::Slider, $question->type);
        $this->assertEquals(1, $question->minimum_value);
        $this->assertEquals(10, $question->maximum_value);
    }

    public function test_questions_are_deleted_when_questionnaire_is_deleted(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        Question::factory()->count(3)->create(['questionnaire_id' => $questionnaire->id]);

        $questionnaire->delete();

        $this->assertEquals(0, Question::count());
    }

    public function test_options_are_deleted_when_question_is_deleted(): void
    {
        $question = Question::factory()->singleChoice()->create();
        QuestionOption::factory()->count(3)->create(['question_id' => $question->id]);

        $question->delete();

        $this->assertEquals(0, QuestionOption::count());
    }
}
