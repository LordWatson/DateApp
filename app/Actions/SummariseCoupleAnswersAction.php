<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Answer;
use App\Models\Questionnaire;
use App\Models\Response;

/**
 * Builds a structured, prompt-friendly summary of a couple's answers to a
 * questionnaire so an AI model can generate a context-aware date night plan.
 *
 * The output intentionally includes:
 *  - the questionnaire's title, description and emoji (so the AI stays on-topic)
 *  - each question's title
 *  - both partners' selected option (or free-text value) for every question
 *
 * The shape is kept small and deterministic so it can be safely embedded in
 * a JSON prompt without leaking unrelated data.
 */
class SummariseCoupleAnswersAction
{
    /**
     * @return array{
     *     questionnaire: array{title: string, description: ?string, emoji: ?string, is_intimacy: bool},
     *     partner_one: array{name: string, answers: list<array{question: string, answer: string}>},
     *     partner_two: array{name: string, answers: list<array{question: string, answer: string}>}
     * }
     */
    public function execute(
        Questionnaire $questionnaire,
        Response $partnerOneResponse,
        Response $partnerTwoResponse,
    ): array {
        $partnerOneResponse->loadMissing(['user', 'answers.question', 'answers.questionOption']);
        $partnerTwoResponse->loadMissing(['user', 'answers.question', 'answers.questionOption']);

        return [
            'questionnaire' => [
                'title' => $questionnaire->title,
                'description' => $questionnaire->description,
                'emoji' => $questionnaire->emoji,
                'is_intimacy' => (bool) $questionnaire->is_intimacy,
            ],
            'partner_one' => [
                'name' => $partnerOneResponse->user?->name ?? 'Partner One',
                'answers' => $this->summariseAnswers($partnerOneResponse),
            ],
            'partner_two' => [
                'name' => $partnerTwoResponse->user?->name ?? 'Partner Two',
                'answers' => $this->summariseAnswers($partnerTwoResponse),
            ],
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function summariseAnswers(Response $response): array
    {
        return $response->answers
            ->sortBy(fn (Answer $answer) => $answer->question?->display_order ?? 0)
            ->map(fn (Answer $answer): array => [
                'question' => (string) ($answer->question?->title ?? ''),
                'answer' => $this->formatAnswerValue($answer),
            ])
            ->filter(fn (array $row): bool => $row['question'] !== '' && $row['answer'] !== '')
            ->values()
            ->all();
    }

    private function formatAnswerValue(Answer $answer): string
    {
        if ($answer->questionOption !== null) {
            return (string) $answer->questionOption->title;
        }

        return trim((string) $answer->value);
    }
}
