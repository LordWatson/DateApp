<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Support\Collection;

class CompatibilityService
{
    public function calculate(User $user, Questionnaire $questionnaire): ?array
    {
        $partner = $user->partner;

        if (! $partner) {
            return null;
        }

        $userResponse = Response::with('answers.question', 'answers.questionOption')
            ->where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->latest('id')
            ->first();

        $partnerResponse = Response::with('answers.question', 'answers.questionOption')
            ->where('user_id', $partner->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->latest('id')
            ->first();

        if (! $userResponse || ! $partnerResponse) {
            return null;
        }

        $questions = $questionnaire->questions()->with('options')->get();

        $matched = collect();
        $different = collect();
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($questions as $question) {
            if ($question->type === QuestionType::Text || $question->type === QuestionType::TextArea) {
                $userAnswer = $this->getTextAnswer($userResponse, $question->id);
                $partnerAnswer = $this->getTextAnswer($partnerResponse, $question->id);

                if ($userAnswer || $partnerAnswer) {
                    $matched->push([
                        'question' => $question->title,
                        'emoji' => $question->emoji,
                        'user_answer' => $userAnswer,
                        'partner_answer' => $partnerAnswer,
                        'type' => 'text',
                    ]);
                }

                continue;
            }

            $score = match ($question->type) {
                QuestionType::SingleChoice => $this->scoreSingleChoice($userResponse, $partnerResponse, $question->id),
                QuestionType::MultipleChoice => $this->scoreMultipleChoice($userResponse, $partnerResponse, $question->id),
                QuestionType::Slider => $this->scoreSlider($userResponse, $partnerResponse, $question->id, $question->minimum_value ?? 1, $question->maximum_value ?? 10),
                default => null,
            };

            if ($score === null) {
                continue;
            }

            $totalScore += $score;
            $totalWeight += 1;

            $userValues = $this->getAnswerValues($userResponse, $question->id);
            $partnerValues = $this->getAnswerValues($partnerResponse, $question->id);

            $entry = [
                'question' => $question->title,
                'emoji' => $question->emoji,
                'user_answer' => $this->formatAnswerLabels($userResponse, $question),
                'partner_answer' => $this->formatAnswerLabels($partnerResponse, $question),
                'score' => $score,
                'type' => $question->type->value,
            ];

            if ($score >= 0.8) {
                $matched->push($entry);
            } else {
                $different->push($entry);
            }
        }

        $overallPercentage = $totalWeight > 0
            ? (int) round(($totalScore / $totalWeight) * 100)
            : 0;

        return [
            'percentage' => $overallPercentage,
            'matched' => $matched->values()->toArray(),
            'different' => $different->values()->toArray(),
            'breakdown' => [
                'total_questions' => $totalWeight,
                'matched_count' => $matched->count(),
                'different_count' => $different->count(),
            ],
        ];
    }

    private function scoreSingleChoice(Response $userResponse, Response $partnerResponse, int $questionId): float
    {
        $userValue = $this->getAnswerValues($userResponse, $questionId)->first();
        $partnerValue = $this->getAnswerValues($partnerResponse, $questionId)->first();

        if ($userValue === null || $partnerValue === null) {
            return 0;
        }

        return $userValue === $partnerValue ? 1.0 : 0.0;
    }

    private function scoreMultipleChoice(Response $userResponse, Response $partnerResponse, int $questionId): float
    {
        $userValues = $this->getAnswerValues($userResponse, $questionId);
        $partnerValues = $this->getAnswerValues($partnerResponse, $questionId);

        if ($userValues->isEmpty() && $partnerValues->isEmpty()) {
            return 1.0;
        }

        if ($userValues->isEmpty() || $partnerValues->isEmpty()) {
            return 0.0;
        }

        $intersection = $userValues->intersect($partnerValues)->count();
        $union = $userValues->merge($partnerValues)->unique()->count();

        return $union > 0 ? $intersection / $union : 0.0;
    }

    private function scoreSlider(Response $userResponse, Response $partnerResponse, int $questionId, int $min, int $max): float
    {
        $userValue = (float) ($this->getTextAnswer($userResponse, $questionId) ?? 0);
        $partnerValue = (float) ($this->getTextAnswer($partnerResponse, $questionId) ?? 0);

        $range = $max - $min;

        if ($range === 0) {
            return 1.0;
        }

        $distance = abs($userValue - $partnerValue);

        return max(0.0, 1.0 - ($distance / $range));
    }

    private function getAnswerValues(Response $response, int $questionId): Collection
    {
        return $response->answers
            ->where('question_id', $questionId)
            ->pluck('value')
            ->filter()
            ->values();
    }

    private function getTextAnswer(Response $response, int $questionId): ?string
    {
        return $response->answers
            ->where('question_id', $questionId)
            ->first()
            ?->value;
    }

    private function formatAnswerLabels(Response $response, mixed $question): string
    {
        $answers = $response->answers->where('question_id', $question->id);

        if ($question->type === QuestionType::Slider || $question->type === QuestionType::Text || $question->type === QuestionType::TextArea) {
            return $answers->first()?->value ?? '';
        }

        return $answers->map(function ($answer) {
            $label = $answer->questionOption?->title ?? $answer->value;
            $emoji = $answer->questionOption?->emoji;

            return $emoji ? "{$emoji} {$label}" : $label;
        })->implode(', ');
    }
}
