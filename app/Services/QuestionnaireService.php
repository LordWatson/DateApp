<?php

namespace App\Services;

use App\Enums\CompletionStatus;
use App\Events\QuestionnaireCompleted;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class QuestionnaireService
{
    public function findOrCreateResponse(User $user, Questionnaire $questionnaire): Response
    {
        return Response::firstOrCreate(
            ['user_id' => $user->id, 'questionnaire_id' => $questionnaire->id],
            ['status' => CompletionStatus::InProgress, 'started_at' => Carbon::now()],
        );
    }

    public function getResponseWithAnswers(User $user, Questionnaire $questionnaire): ?Response
    {
        return Response::with('answers')
            ->where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->first();
    }

    public function saveAnswer(Response $response, Question $question, mixed $value): void
    {
        if ($question->type->value === 'multiple_choice') {
            $this->saveMultipleChoiceAnswer($response, $question, (array) $value);
        } elseif (in_array($question->type->value, ['single_choice'])) {
            $this->saveSingleChoiceAnswer($response, $question, $value);
        } else {
            $this->saveTextAnswer($response, $question, $value);
        }
    }

    private function saveSingleChoiceAnswer(Response $response, Question $question, mixed $value): void
    {
        Answer::where('response_id', $response->id)
            ->where('question_id', $question->id)
            ->delete();

        $option = $question->options()->where('value', $value)->first();

        Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'question_option_id' => $option?->id,
            'value' => $value,
            'created_at' => Carbon::now(),
        ]);
    }

    private function saveMultipleChoiceAnswer(Response $response, Question $question, array $values): void
    {
        Answer::where('response_id', $response->id)
            ->where('question_id', $question->id)
            ->delete();

        foreach ($values as $value) {
            $option = $question->options()->where('value', $value)->first();

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'question_option_id' => $option?->id,
                'value' => $value,
                'created_at' => Carbon::now(),
            ]);
        }
    }

    private function saveTextAnswer(Response $response, Question $question, mixed $value): void
    {
        Answer::updateOrCreate(
            ['response_id' => $response->id, 'question_id' => $question->id],
            ['value' => $value, 'created_at' => Carbon::now()],
        );
    }

    public function getAnswersForResponse(Response $response): Collection
    {
        return $response->answers()->with('question', 'questionOption')->get();
    }

    public function applyProfile(Response $response, SavedProfile $profile): void
    {
        $profileAnswers = $profile->answers()->with('question')->get();

        foreach ($profileAnswers->groupBy('question_id') as $questionId => $answers) {
            $question = $answers->first()->question;

            if (! $question) {
                continue;
            }

            if ($question->type->value === 'multiple_choice') {
                $this->saveMultipleChoiceAnswer($response, $question, $answers->pluck('value')->toArray());
            } elseif ($question->type->value === 'single_choice') {
                $this->saveSingleChoiceAnswer($response, $question, $answers->first()->value);
            } else {
                $this->saveTextAnswer($response, $question, $answers->first()->value);
            }
        }
    }

    public function completeResponse(Response $response): void
    {
        $response->update([
            'status' => CompletionStatus::Completed,
            'completed_at' => Carbon::now(),
        ]);

        $response->loadMissing(['user', 'questionnaire']);

        QuestionnaireCompleted::dispatch($response->user, $response->questionnaire, $response);
    }

    public function getAnsweredQuestionIds(Response $response): array
    {
        return $response->answers()->pluck('question_id')->unique()->values()->toArray();
    }

    public function getFirstUnansweredOrder(Questionnaire $questionnaire, Response $response): int
    {
        $answeredIds = $this->getAnsweredQuestionIds($response);

        $next = $questionnaire->questions()
            ->whereNotIn('id', $answeredIds)
            ->orderBy('display_order')
            ->first();

        return $next?->display_order ?? $questionnaire->questions()->max('display_order');
    }
}
