<?php

namespace Database\Seeders;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class SoloDatePlannerQuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaire = Questionnaire::updateOrCreate(
            ['slug' => 'plan-a-date-out'],
            [
                'title' => '🗺️ Plan a Date Out',
                'description' => 'Answer a few quick questions and we\'ll craft the perfect date around your time, mood and neighbourhood.',
                'emoji' => '🗺️',
                'cover_image' => null,
                'status' => QuestionnaireStatus::Active,
                'visibility' => QuestionnaireVisibility::Public,
                'estimated_minutes' => 3,
                'display_order' => 0,
                'is_seasonal' => false,
                'is_intimacy' => false,
                'is_solo' => true,
            ],
        );

        // Refresh existing questions/options for idempotency.
        $questionnaire->questions()->each(function (Question $q): void {
            $q->options()->delete();
            $q->delete();
        });

        foreach ($this->questions() as $order => $data) {
            $question = Question::create([
                'questionnaire_id' => $questionnaire->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'emoji' => $data['emoji'],
                'type' => $data['type'],
                'required' => true,
                'minimum_value' => $data['minimum_value'] ?? null,
                'maximum_value' => $data['maximum_value'] ?? null,
                'step_value' => $data['step_value'] ?? null,
                'unit' => $data['unit'] ?? null,
                'display_order' => $order + 1,
            ]);

            foreach ($data['options'] ?? [] as $optionOrder => $option) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'title' => $option['title'],
                    'description' => $option['description'] ?? null,
                    'emoji' => $option['emoji'] ?? null,
                    'value' => $option['value'],
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
                'emoji' => '📅',
                'title' => 'When are you planning this date?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => '⚡', 'title' => 'Tonight', 'value' => 'tonight'],
                    ['emoji' => '🌙', 'title' => 'Tomorrow', 'value' => 'tomorrow'],
                    ['emoji' => '📆', 'title' => 'This weekend', 'value' => 'this_weekend'],
                    ['emoji' => '🗓️', 'title' => 'Next week', 'value' => 'next_week'],
                    ['emoji' => '✨', 'title' => 'Just brainstorming', 'value' => 'brainstorming'],
                ],
            ],
            [
                'emoji' => '⏱️',
                'title' => 'How much time do you have together?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => '⏳', 'title' => 'An hour or two', 'value' => 'short'],
                    ['emoji' => '🕒', 'title' => 'A few hours', 'value' => 'few_hours'],
                    ['emoji' => '🌆', 'title' => 'Half a day', 'value' => 'half_day'],
                    ['emoji' => '🌞', 'title' => 'The whole day', 'value' => 'full_day'],
                ],
            ],
            [
                'emoji' => '🌤️',
                'title' => 'Day time or evening?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => '☀️', 'title' => 'Day time', 'value' => 'day'],
                    ['emoji' => '🌇', 'title' => 'Late afternoon into evening', 'value' => 'afternoon_evening'],
                    ['emoji' => '🌙', 'title' => 'Evening', 'value' => 'evening'],
                    ['emoji' => '✨', 'title' => 'Surprise me', 'value' => 'any'],
                ],
            ],
            [
                'emoji' => '🎭',
                'title' => 'What kind of vibe are you after?',
                'type' => QuestionType::MultipleChoice,
                'options' => [
                    ['emoji' => '🍽️', 'title' => 'Food-focused', 'value' => 'food'],
                    ['emoji' => '🎨', 'title' => 'Cultural or artsy', 'value' => 'culture'],
                    ['emoji' => '🎢', 'title' => 'Playful experience', 'value' => 'experience'],
                    ['emoji' => '🌿', 'title' => 'Outdoors', 'value' => 'outdoors'],
                    ['emoji' => '🍸', 'title' => 'Drinks & nightlife', 'value' => 'nightlife'],
                    ['emoji' => '💆', 'title' => 'Relaxing & slow', 'value' => 'relaxing'],
                    ['emoji' => '🎶', 'title' => 'Live music or shows', 'value' => 'music'],
                ],
            ],
            [
                'emoji' => '💸',
                'title' => 'What\'s your budget for this date?',
                'type' => QuestionType::SingleChoice,
                'options' => [
                    ['emoji' => '🪙', 'title' => 'Keep it free or very cheap', 'value' => 'free'],
                    ['emoji' => '☕', 'title' => 'Light spend', 'value' => 'light'],
                    ['emoji' => '🍽️', 'title' => 'Comfortable', 'value' => 'comfortable'],
                    ['emoji' => '🥂', 'title' => 'Treat ourselves', 'value' => 'treat'],
                    [
                        'emoji' => '💷',
                        'title' => 'Custom amount',
                        'description' => 'Tell us exactly what you\'d like to spend',
                        'value' => 'custom_amount',
                    ],
                ],
            ],
            [
                'emoji' => '🚗',
                'title' => 'How far are you happy to travel?',
                'description' => 'Drag the slider to set a maximum travel time in 30-minute steps.',
                'type' => QuestionType::Slider,
                'minimum_value' => 0,
                'maximum_value' => 240,
                'step_value' => 30,
                'unit' => 'minutes',
                'options' => [],
            ],
            [
                'emoji' => '🌡️',
                'title' => 'How energetic do you want it to be?',
                'type' => QuestionType::Slider,
                'minimum_value' => 1,
                'maximum_value' => 10,
                'options' => [],
            ],
            [
                'emoji' => '💕',
                'title' => 'How romantic should the vibe be?',
                'type' => QuestionType::Slider,
                'minimum_value' => 1,
                'maximum_value' => 10,
                'options' => [],
            ],
            [
                'emoji' => '🍷',
                'title' => 'Anything to build the date around?',
                'description' => 'A favourite cuisine, a place you\'ve wanted to try, an activity you both love…',
                'type' => QuestionType::TextArea,
                'options' => [],
            ],
            [
                'emoji' => '🚫',
                'title' => 'Anything to avoid?',
                'description' => 'Allergies, dislikes, places to skip — anything we should steer clear of.',
                'type' => QuestionType::TextArea,
                'options' => [],
            ],
        ];
    }
}
