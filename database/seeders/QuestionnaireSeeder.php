<?php

namespace Database\Seeders;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        // Already seeded: the slug is unique and admins may have edited the questions since,
        // so only fill in signals that are still missing.
        if ($existing = Questionnaire::where('slug', 'tonight')->first()) {
            $this->backfillSignals($existing);

            return;
        }

        $questionnaire = Questionnaire::create([
            'title' => '❤️ Tonight',
            'slug' => 'tonight',
            'description' => 'Plan an evening of sexual intimacy together.',
            'emoji' => '❤️',
            'cover_image' => null,
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
            'estimated_minutes' => 3,
            'display_order' => 1,
            'is_intimacy' => true,
            'active_from' => null,
            'active_until' => null,
        ]);

        $this->seedQuestions($questionnaire);
    }

    /**
     * Tag options seeded before `signals` existed. Matches by question title and
     * option value, and never overwrites signals that are already set.
     */
    private function backfillSignals(Questionnaire $questionnaire): void
    {
        foreach ($this->questions() as $data) {
            foreach ($data['options'] as $option) {
                if (! isset($option['signals'])) {
                    continue;
                }

                QuestionOption::query()
                    ->whereIn('question_id', $questionnaire->questions()->where('title', $data['title'])->select('id'))
                    ->where('value', $option['value'])
                    ->whereNull('signals')
                    ->get()
                    ->each->update(['signals' => $option['signals']]);
            }
        }
    }

    private function seedQuestions(Questionnaire $questionnaire): void
    {
        foreach ($this->questions() as $order => $data) {
            $question = Question::create([
                'questionnaire_id' => $questionnaire->id,
                'title' => $data['title'],
                'description' => null,
                'emoji' => $data['emoji'],
                'type' => $data['type'],
                'required' => true,
                'minimum_value' => $data['minimum_value'] ?? null,
                'maximum_value' => $data['maximum_value'] ?? null,
                'display_order' => $order + 1,
            ]);

            foreach ($data['options'] as $optionOrder => $option) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'title' => $option['title'],
                    'description' => null,
                    'emoji' => $option['emoji'],
                    'value' => $option['value'],
                    'signals' => $option['signals'] ?? null,
                    'display_order' => $optionOrder + 1,
                ]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function questions(): array
    {
        return [
            [
                'emoji' => '❤️',
                'title' => 'How are you feeling tonight?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => '😈', 'title' => 'Dominant', 'value' => 'dominant'],
                    ['emoji' => '🥰', 'title' => 'Submissive', 'value' => 'submissive'],
                    ['emoji' => '🤝', 'title' => 'Go with the flow', 'value' => 'go_with_the_flow'],
                ],
            ],
            [
                'emoji' => '🔥',
                'title' => 'How adventurous are you feeling?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => '😊', 'title' => 'Keeping it simple', 'value' => 'simple'],
                    ['emoji' => '😉', 'title' => 'Feeling playful', 'value' => 'playful'],
                    ['emoji' => '🔥', 'title' => "Let's surprise each other", 'value' => 'surprise'],
                ],
            ],
            [
                'emoji' => '💕',
                'title' => 'How romantic are you feeling?',
                'type' => QuestionType::Slider,
                'minimum_value' => 1,
                'maximum_value' => 10,
                'options' => [],
            ],
            [
                'emoji' => '✨',
                'title' => 'Would you like to include any accessories or extras tonight?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => null, 'title' => 'No', 'value' => 'no'],
                    ['emoji' => null, 'title' => 'Maybe', 'value' => 'maybe'],
                    ['emoji' => null, 'title' => 'Yes', 'value' => 'yes'],
                ],
            ],
            [
                'emoji' => '🎵',
                'title' => 'Would music make the evening better?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => null, 'title' => 'Absolutely', 'value' => 'absolutely', 'signals' => ['music']],
                    ['emoji' => null, 'title' => 'Maybe', 'value' => 'maybe'],
                    ['emoji' => null, 'title' => 'No preference', 'value' => 'no_preference'],
                ],
            ],
            [
                'emoji' => '🕯️',
                'title' => 'Set the mood?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => null, 'title' => 'Candles', 'value' => 'candles', 'signals' => ['romance']],
                    ['emoji' => null, 'title' => 'Dim lights', 'value' => 'dim_lights'],
                    ['emoji' => null, 'title' => 'Natural', 'value' => 'natural'],
                    ['emoji' => null, 'title' => 'No preference', 'value' => 'no_preference'],
                ],
            ],
            [
                'emoji' => '💋',
                'title' => 'How important is kissing tonight?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => null, 'title' => 'Very', 'value' => 'very'],
                    ['emoji' => null, 'title' => 'Some', 'value' => 'some'],
                    ['emoji' => null, 'title' => 'Not important', 'value' => 'not_important'],
                ],
            ],
            [
                'emoji' => '🤗',
                'title' => 'Would you enjoy a massage first?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => null, 'title' => 'Yes', 'value' => 'yes', 'signals' => ['relaxing']],
                    ['emoji' => null, 'title' => 'Maybe', 'value' => 'maybe'],
                    ['emoji' => null, 'title' => 'Skip it', 'value' => 'skip'],
                ],
            ],
            [
                'emoji' => '👕',
                'title' => 'Dress up a little?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => null, 'title' => 'Yes', 'value' => 'yes'],
                    ['emoji' => null, 'title' => 'No', 'value' => 'no'],
                    ['emoji' => null, 'title' => 'Surprise me', 'value' => 'surprise'],
                ],
            ],
            [
                'emoji' => '🍷',
                'title' => 'Favourite way to start the evening?',
                'type' => QuestionType::Text,
                'options' => [],
            ],
            [
                'emoji' => '❤️',
                'title' => "Anything you'd love more of tonight?",
                'type' => QuestionType::TextArea,
                'options' => [],
            ],
            [
                'emoji' => '🚫',
                'title' => "Anything you'd rather avoid tonight?",
                'type' => QuestionType::TextArea,
                'options' => [],
            ],
            [
                'emoji' => '😊',
                'title' => "Anything you'd like your partner to know?",
                'type' => QuestionType::TextArea,
                'options' => [],
            ],
        ];
    }
}
