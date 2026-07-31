<?php

namespace Database\Seeders;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class IntimacyQuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->questionnaires() as $data) {
            $questions = $data['questions'];
            unset($data['questions']);

            $questionnaire = Questionnaire::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            foreach ($questions as $questionData) {
                $options = $questionData['options'] ?? [];
                unset($questionData['options']);

                $question = Question::firstOrCreate(
                    [
                        'questionnaire_id' => $questionnaire->id,
                        'display_order' => $questionData['display_order'],
                    ],
                    array_merge($questionData, ['questionnaire_id' => $questionnaire->id])
                );

                foreach ($options as $optionData) {
                    QuestionOption::firstOrCreate(
                        [
                            'question_id' => $question->id,
                            'value' => $optionData['value'],
                        ],
                        array_merge($optionData, ['question_id' => $question->id])
                    );
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questionnaires(): array
    {
        return [
            [
                'title' => 'Tonight’s Mood',
                'slug' => 'intimacy-tonights-mood',
                'description' => 'Share the sexual vibe you’re craving tonight and let the magic follow.',
                'emoji' => '🔥',
                'status' => QuestionnaireStatus::Active->value,
                'visibility' => QuestionnaireVisibility::Public->value,
                'estimated_minutes' => 5,
                'display_order' => 100,
                'is_seasonal' => false,
                'is_intimacy' => true,
                'artwork' => 'intimacy_mood',
                'questions' => [
                    [
                        'title' => 'What kind of night are you dreaming of?',
                        'description' => 'Set the tone for your evening together.',
                        'emoji' => '✨',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '💋', 'title' => 'Slow and sensual', 'value' => 'sensual', 'display_order' => 1],
                            ['emoji' => '😈', 'title' => 'Playful and teasing', 'value' => 'playful', 'display_order' => 2],
                            ['emoji' => '🔥', 'title' => 'Passionate and intense', 'value' => 'passionate', 'display_order' => 3],
                            ['emoji' => '🕯️', 'title' => 'Romantic and cosy', 'value' => 'romantic', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'How much energy do you have?',
                        'description' => 'Be honest — soft nights are just as sexy.',
                        'emoji' => '⚡',
                        'type' => 'slider',
                        'display_order' => 2,
                        'required' => true,
                        'minimum_value' => 1,
                        'maximum_value' => 10,
                    ],
                    [
                        'title' => 'Where should the night begin?',
                        'description' => 'Choose the setting that turns you on tonight.',
                        'emoji' => '📍',
                        'type' => 'single_choice',
                        'display_order' => 3,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🛁', 'title' => 'A long, warm bath together', 'value' => 'bath', 'display_order' => 1],
                            ['emoji' => '🛏️', 'title' => 'Straight to bed', 'value' => 'bed', 'display_order' => 2],
                            ['emoji' => '🛋️', 'title' => 'On the sofa with wine', 'value' => 'sofa', 'display_order' => 3],
                            ['emoji' => '🌙', 'title' => 'Somewhere unexpected', 'value' => 'unexpected', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'Pick your soundtrack',
                        'description' => 'Music sets the whole mood.',
                        'emoji' => '🎶',
                        'type' => 'single_choice',
                        'display_order' => 4,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🎷', 'title' => 'Smooth jazz & candles', 'value' => 'jazz', 'display_order' => 1],
                            ['emoji' => '🎧', 'title' => 'Slow R&B', 'value' => 'rnb', 'display_order' => 2],
                            ['emoji' => '🥁', 'title' => 'Deep house beats', 'value' => 'house', 'display_order' => 3],
                            ['emoji' => '🤫', 'title' => 'Just us, no music', 'value' => 'silence', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'Anything I should know?',
                        'description' => 'A whisper for your partner — only they will see it.',
                        'emoji' => '💌',
                        'type' => 'textarea',
                        'display_order' => 5,
                        'required' => false,
                    ],
                ],
            ],
            [
                'title' => 'Desires & Boundaries',
                'slug' => 'intimacy-desires-and-boundaries',
                'description' => 'A playful sexual check-in on what feels good and what’s off the menu.',
                'emoji' => '💞',
                'status' => QuestionnaireStatus::Active->value,
                'visibility' => QuestionnaireVisibility::Public->value,
                'estimated_minutes' => 10,
                'display_order' => 101,
                'is_seasonal' => false,
                'is_intimacy' => true,
                'artwork' => 'intimacy_desires',
                'questions' => [
                    [
                        'title' => 'Which of these are you curious about?',
                        'description' => 'Pick as many as you like — no pressure.',
                        'emoji' => '🌹',
                        'type' => 'multiple_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '💆', 'title' => 'Full body massage', 'value' => 'massage', 'display_order' => 1],
                            ['emoji' => '👀', 'title' => 'Eye contact & slow kissing', 'value' => 'eye_contact', 'display_order' => 2],
                            ['emoji' => '🎭', 'title' => 'Trying a little roleplay', 'value' => 'roleplay', 'display_order' => 3],
                            ['emoji' => '🪶', 'title' => 'Teasing with textures', 'value' => 'textures', 'display_order' => 4],
                            ['emoji' => '🕯️', 'title' => 'Candlelit slow build-up', 'value' => 'slow_build', 'display_order' => 5],
                            ['emoji' => '🎲', 'title' => 'Let a game decide', 'value' => 'game', 'display_order' => 6],
                        ],
                    ],
                    [
                        'title' => 'How adventurous are you feeling this week?',
                        'description' => '1 = cosy classic · 10 = surprise me completely',
                        'emoji' => '🌶️',
                        'type' => 'slider',
                        'display_order' => 2,
                        'required' => true,
                        'minimum_value' => 1,
                        'maximum_value' => 10,
                    ],
                    [
                        'title' => 'What’s off the table tonight?',
                        'description' => 'Boundaries are sexy. Share anything you want to skip.',
                        'emoji' => '🛑',
                        'type' => 'textarea',
                        'display_order' => 3,
                        'required' => false,
                    ],
                    [
                        'title' => 'What makes you feel most desired?',
                        'description' => 'The little things that light you up.',
                        'emoji' => '💘',
                        'type' => 'single_choice',
                        'display_order' => 4,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🗣️', 'title' => 'Being told exactly what they want', 'value' => 'words', 'display_order' => 1],
                            ['emoji' => '🤲', 'title' => 'Slow, deliberate touch', 'value' => 'touch', 'display_order' => 2],
                            ['emoji' => '👗', 'title' => 'Being dressed up for', 'value' => 'dress_up', 'display_order' => 3],
                            ['emoji' => '⏳', 'title' => 'Long, drawn-out anticipation', 'value' => 'anticipation', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'A fantasy I’d love to explore',
                        'description' => 'Whisper it here — your partner will see it after they finish too.',
                        'emoji' => '💭',
                        'type' => 'textarea',
                        'display_order' => 5,
                        'required' => false,
                    ],
                ],
            ],
            [
                'title' => 'After Dark Preferences',
                'slug' => 'intimacy-after-dark-preferences',
                'description' => 'Fine-tune the little details that make your sexual intimacy time unforgettable.',
                'emoji' => '🌙',
                'status' => QuestionnaireStatus::Active->value,
                'visibility' => QuestionnaireVisibility::Public->value,
                'estimated_minutes' => 8,
                'display_order' => 102,
                'is_seasonal' => false,
                'is_intimacy' => true,
                'artwork' => 'intimacy_after_dark',
                'questions' => [
                    [
                        'title' => 'How do you love the room to feel?',
                        'description' => 'Set the sensory scene.',
                        'emoji' => '🕯️',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🌑', 'title' => 'Pitch dark', 'value' => 'dark', 'display_order' => 1],
                            ['emoji' => '🕯️', 'title' => 'Candlelight only', 'value' => 'candles', 'display_order' => 2],
                            ['emoji' => '💡', 'title' => 'Soft warm lamps', 'value' => 'lamps', 'display_order' => 3],
                            ['emoji' => '🌈', 'title' => 'Colourful mood lights', 'value' => 'colour', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'Preferred pace tonight?',
                        'description' => 'Slow burn or fast fire?',
                        'emoji' => '⏱️',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🐢', 'title' => 'Slow and unhurried', 'value' => 'slow', 'display_order' => 1],
                            ['emoji' => '🌊', 'title' => 'Waves — slow then fast', 'value' => 'waves', 'display_order' => 2],
                            ['emoji' => '⚡', 'title' => 'Fast and hungry', 'value' => 'fast', 'display_order' => 3],
                            ['emoji' => '🎲', 'title' => 'Whatever you decide', 'value' => 'surprise', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'Choose your love language for tonight',
                        'description' => 'Multiple choice — mix and match.',
                        'emoji' => '💗',
                        'type' => 'multiple_choice',
                        'display_order' => 3,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🗣️', 'title' => 'Dirty whispers', 'value' => 'words', 'display_order' => 1],
                            ['emoji' => '💋', 'title' => 'Long deep kissing', 'value' => 'kissing', 'display_order' => 2],
                            ['emoji' => '🤲', 'title' => 'Wandering hands', 'value' => 'touch', 'display_order' => 3],
                            ['emoji' => '👀', 'title' => 'Intense eye contact', 'value' => 'eyes', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'After the fun, I want to…',
                        'description' => 'Aftercare matters too.',
                        'emoji' => '🥰',
                        'type' => 'single_choice',
                        'display_order' => 4,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🤗', 'title' => 'Cuddle for hours', 'value' => 'cuddle', 'display_order' => 1],
                            ['emoji' => '🛁', 'title' => 'Share a bath', 'value' => 'bath', 'display_order' => 2],
                            ['emoji' => '🍫', 'title' => 'Snack in bed', 'value' => 'snack', 'display_order' => 3],
                            ['emoji' => '😴', 'title' => 'Fall asleep tangled up', 'value' => 'sleep', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'A secret sweet nothing',
                        'description' => 'One thing you want your partner to hear tonight.',
                        'emoji' => '💬',
                        'type' => 'text',
                        'display_order' => 5,
                        'required' => false,
                    ],
                ],
            ],
        ];
    }
}
