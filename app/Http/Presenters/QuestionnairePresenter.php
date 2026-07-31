<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response as QuestionnaireResponse;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shapes questionnaire, question and answer models into the array
 * payloads Inertia sends to the front-end. Keeps controllers thin
 * and centralises the presentation contract in a single place.
 */
final class QuestionnairePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function questionnaire(Questionnaire $questionnaire, bool $includeQuestionCount = true): array
    {
        return [
            'id' => $questionnaire->id,
            'title' => $questionnaire->title,
            'slug' => $questionnaire->slug,
            'description' => $questionnaire->description,
            'emoji' => $questionnaire->emoji,
            'estimated_minutes' => $questionnaire->estimated_minutes,
            'question_count' => $includeQuestionCount ? $questionnaire->questions()->count() : null,
            'is_solo' => $questionnaire->is_solo,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionnaireBrief(Questionnaire $questionnaire): array
    {
        return [
            'id' => $questionnaire->id,
            'title' => $questionnaire->title,
            'slug' => $questionnaire->slug,
            'estimated_minutes' => $questionnaire->estimated_minutes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function question(Question $question): array
    {
        return [
            'id' => $question->id,
            'title' => $question->title,
            'description' => $question->description,
            'emoji' => $question->emoji,
            'type' => $question->type->value,
            'required' => $question->required,
            'minimum_value' => $question->minimum_value,
            'maximum_value' => $question->maximum_value,
            'step_value' => $question->step_value,
            'unit' => $question->unit,
            'display_order' => $question->display_order,
            'options' => $question->options->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'description' => $o->description,
                'emoji' => $o->emoji,
                'value' => $o->value,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionForSummary(Question $question): array
    {
        return [
            'id' => $question->id,
            'title' => $question->title,
            'emoji' => $question->emoji,
            'type' => $question->type->value,
            'unit' => $question->unit,
            'display_order' => $question->display_order,
            'options' => $question->options->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'emoji' => $o->emoji,
                'value' => $o->value,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function questionForComparison(Question $question): array
    {
        return [
            'id' => $question->id,
            'title' => $question->title,
            'emoji' => $question->emoji,
            'type' => $question->type->value,
            'display_order' => $question->display_order,
        ];
    }

    /**
     * @param  Collection<int, Answer>  $answers
     * @return Collection<int, array<string, mixed>>
     */
    public function answersForQuestion(Collection $answers, int $questionId): Collection
    {
        return $answers->where('question_id', $questionId)->map(fn ($a) => [
            'value' => $a->value,
            'option_title' => $a->questionOption?->title,
            'option_emoji' => $a->questionOption?->emoji,
        ])->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function savedProfiles(User $user): array
    {
        return $user->savedProfiles->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'emoji' => $p->emoji,
            'colour' => $p->colour,
        ])->all();
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  Collection<int, Answer>  $answers
     * @return Collection<int, array<string, mixed>>
     */
    public function summaryGroupedAnswers(Collection $questions, Collection $answers): Collection
    {
        return $questions->map(fn (Question $question) => [
            'question' => $this->questionForSummary($question),
            'answers' => $this->answersForQuestion($answers, $question->id),
        ]);
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  Collection<int, Answer>  $userAnswers
     * @param  Collection<int, Answer>  $partnerAnswers
     * @return Collection<int, array<string, mixed>>
     */
    public function comparisonGroupedAnswers(Collection $questions, Collection $userAnswers, Collection $partnerAnswers): Collection
    {
        return $questions->map(fn (Question $question) => [
            'question' => $this->questionForComparison($question),
            'my_answers' => $this->answersForQuestion($userAnswers, $question->id),
            'partner_answers' => $this->answersForQuestion($partnerAnswers, $question->id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function progress(int $order, int $total, int $answeredCount): array
    {
        return [
            'current' => $order,
            'total' => $total,
            'percentage' => $total > 0 ? (int) round(($order / $total) * 100) : 0,
            'answered_count' => $answeredCount,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function responseSummary(?QuestionnaireResponse $response, int $answeredCount, int $resumeOrder): ?array
    {
        if (! $response) {
            return null;
        }

        return [
            'status' => $response->status->value,
            'answered_count' => $answeredCount,
            'resume_order' => $resumeOrder,
        ];
    }
}
