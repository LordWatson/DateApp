<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class SeasonalQuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaires = [
            [
                'title' => "Valentine's Day",
                'slug' => 'valentines-day',
                'description' => 'Make this Valentine\'s Day unforgettable with a perfectly planned evening.',
                'emoji' => '💝',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 8,
                'display_order' => 10,
                'is_seasonal' => true,
                'artwork' => 'valentines',
                'questions' => [
                    [
                        'title' => 'How do you want to celebrate Valentine\'s Day?',
                        'description' => 'Choose the vibe that feels right.',
                        'emoji' => '💕',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🍽️', 'title' => 'Romantic dinner', 'value' => 'dinner', 'display_order' => 1],
                            ['emoji' => '🏠', 'title' => 'Cosy night in', 'value' => 'home', 'display_order' => 2],
                            ['emoji' => '🌹', 'title' => 'Surprise me', 'value' => 'surprise', 'display_order' => 3],
                            ['emoji' => '🎭', 'title' => 'Experience or show', 'value' => 'experience', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What gift style speaks to you?',
                        'description' => 'Pick what feels most meaningful.',
                        'emoji' => '🎁',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '💐', 'title' => 'Flowers & chocolates', 'value' => 'classic', 'display_order' => 1],
                            ['emoji' => '💎', 'title' => 'Jewellery', 'value' => 'jewellery', 'display_order' => 2],
                            ['emoji' => '✉️', 'title' => 'Heartfelt letter', 'value' => 'letter', 'display_order' => 3],
                            ['emoji' => '🎟️', 'title' => 'Experience gift', 'value' => 'experience', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What mood are you in?',
                        'description' => 'Set the tone for the evening.',
                        'emoji' => '✨',
                        'type' => 'single_choice',
                        'display_order' => 3,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🕯️', 'title' => 'Deeply romantic', 'value' => 'romantic', 'display_order' => 1],
                            ['emoji' => '😂', 'title' => 'Fun and playful', 'value' => 'playful', 'display_order' => 2],
                            ['emoji' => '🔥', 'title' => 'Passionate', 'value' => 'passionate', 'display_order' => 3],
                            ['emoji' => '😌', 'title' => 'Relaxed and easy', 'value' => 'relaxed', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Anniversary',
                'slug' => 'anniversary',
                'description' => 'Celebrate your love story with a perfectly crafted anniversary evening.',
                'emoji' => '💍',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 10,
                'display_order' => 11,
                'is_seasonal' => true,
                'artwork' => 'anniversary',
                'questions' => [
                    [
                        'title' => 'How do you want to mark this anniversary?',
                        'description' => 'Choose what feels most special.',
                        'emoji' => '🥂',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🍾', 'title' => 'Champagne & fine dining', 'value' => 'fine_dining', 'display_order' => 1],
                            ['emoji' => '🌍', 'title' => 'Weekend getaway', 'value' => 'getaway', 'display_order' => 2],
                            ['emoji' => '🏠', 'title' => 'Recreate our first date', 'value' => 'first_date', 'display_order' => 3],
                            ['emoji' => '🎁', 'title' => 'Surprise experience', 'value' => 'surprise', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What do you want to reflect on together?',
                        'description' => 'Pick a theme for your evening.',
                        'emoji' => '💭',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '📸', 'title' => 'Our favourite memories', 'value' => 'memories', 'display_order' => 1],
                            ['emoji' => '🔮', 'title' => 'Dreams for the future', 'value' => 'future', 'display_order' => 2],
                            ['emoji' => '❤️', 'title' => 'How much we\'ve grown', 'value' => 'growth', 'display_order' => 3],
                            ['emoji' => '🎉', 'title' => 'Just celebrate!', 'value' => 'celebrate', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Birthday Celebration',
                'slug' => 'birthday-celebration',
                'description' => 'Plan the perfect birthday surprise for your partner.',
                'emoji' => '🎂',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 8,
                'display_order' => 12,
                'is_seasonal' => true,
                'artwork' => 'birthday',
                'questions' => [
                    [
                        'title' => 'What kind of birthday celebration?',
                        'description' => 'Set the scale for the day.',
                        'emoji' => '🎉',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🎊', 'title' => 'Big surprise party', 'value' => 'party', 'display_order' => 1],
                            ['emoji' => '🍰', 'title' => 'Intimate dinner for two', 'value' => 'intimate', 'display_order' => 2],
                            ['emoji' => '🎢', 'title' => 'Fun day out', 'value' => 'day_out', 'display_order' => 3],
                            ['emoji' => '🛁', 'title' => 'Pamper day at home', 'value' => 'pamper', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What gift approach?',
                        'description' => 'Choose your gifting style.',
                        'emoji' => '🎁',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🛍️', 'title' => 'Something they\'ve been wanting', 'value' => 'wishlist', 'display_order' => 1],
                            ['emoji' => '🎟️', 'title' => 'An experience', 'value' => 'experience', 'display_order' => 2],
                            ['emoji' => '🤝', 'title' => 'Quality time together', 'value' => 'time', 'display_order' => 3],
                            ['emoji' => '💌', 'title' => 'Heartfelt surprise', 'value' => 'surprise', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Weekend Away',
                'slug' => 'weekend-away',
                'description' => 'Plan your perfect couple\'s escape together.',
                'emoji' => '🏨',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 10,
                'display_order' => 13,
                'is_seasonal' => false,
                'artwork' => 'weekend_away',
                'questions' => [
                    [
                        'title' => 'What type of destination?',
                        'description' => 'Where does your heart want to go?',
                        'emoji' => '🗺️',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🏖️', 'title' => 'Beach & coast', 'value' => 'beach', 'display_order' => 1],
                            ['emoji' => '🏔️', 'title' => 'Mountains & nature', 'value' => 'mountains', 'display_order' => 2],
                            ['emoji' => '🏙️', 'title' => 'City break', 'value' => 'city', 'display_order' => 3],
                            ['emoji' => '🌿', 'title' => 'Countryside retreat', 'value' => 'countryside', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What\'s the vibe for the trip?',
                        'description' => 'Choose your travel mood.',
                        'emoji' => '✈️',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🧘', 'title' => 'Relaxation & spa', 'value' => 'relax', 'display_order' => 1],
                            ['emoji' => '🎒', 'title' => 'Adventure & explore', 'value' => 'adventure', 'display_order' => 2],
                            ['emoji' => '🍷', 'title' => 'Food & wine', 'value' => 'food', 'display_order' => 3],
                            ['emoji' => '🎭', 'title' => 'Culture & arts', 'value' => 'culture', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Christmas Together',
                'slug' => 'christmas-together',
                'description' => 'Make the festive season magical with your perfect Christmas plan.',
                'emoji' => '🎄',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 8,
                'display_order' => 14,
                'is_seasonal' => true,
                'artwork' => 'christmas',
                'questions' => [
                    [
                        'title' => 'How do you want to spend Christmas?',
                        'description' => 'Choose your festive style.',
                        'emoji' => '🎅',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🏠', 'title' => 'Cosy at home', 'value' => 'home', 'display_order' => 1],
                            ['emoji' => '✈️', 'title' => 'Travel somewhere new', 'value' => 'travel', 'display_order' => 2],
                            ['emoji' => '👨‍👩‍👧', 'title' => 'With family', 'value' => 'family', 'display_order' => 3],
                            ['emoji' => '🎿', 'title' => 'Winter adventure', 'value' => 'adventure', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What Christmas tradition matters most?',
                        'description' => 'Pick your favourite festive ritual.',
                        'emoji' => '🕯️',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🎁', 'title' => 'Exchanging gifts', 'value' => 'gifts', 'display_order' => 1],
                            ['emoji' => '🍽️', 'title' => 'Christmas dinner', 'value' => 'dinner', 'display_order' => 2],
                            ['emoji' => '🎬', 'title' => 'Christmas movies', 'value' => 'movies', 'display_order' => 3],
                            ['emoji' => '🌟', 'title' => 'Decorating together', 'value' => 'decorating', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'New Year\'s Eve',
                'slug' => 'new-years-eve',
                'description' => 'Ring in the new year together in style.',
                'emoji' => '🥂',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 8,
                'display_order' => 15,
                'is_seasonal' => true,
                'artwork' => 'new_year',
                'questions' => [
                    [
                        'title' => 'How do you want to celebrate New Year\'s Eve?',
                        'description' => 'Choose your midnight vibe.',
                        'emoji' => '🎆',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🎉', 'title' => 'Party with friends', 'value' => 'party', 'display_order' => 1],
                            ['emoji' => '🥂', 'title' => 'Intimate dinner for two', 'value' => 'intimate', 'display_order' => 2],
                            ['emoji' => '🏠', 'title' => 'Cosy night in', 'value' => 'home', 'display_order' => 3],
                            ['emoji' => '🌃', 'title' => 'Watch the fireworks', 'value' => 'fireworks', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What do you want to set together for the new year?',
                        'description' => 'Choose your shared intention.',
                        'emoji' => '🔮',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🌍', 'title' => 'Travel goals', 'value' => 'travel', 'display_order' => 1],
                            ['emoji' => '💪', 'title' => 'Health & wellness', 'value' => 'health', 'display_order' => 2],
                            ['emoji' => '💰', 'title' => 'Financial goals', 'value' => 'finance', 'display_order' => 3],
                            ['emoji' => '❤️', 'title' => 'Relationship goals', 'value' => 'relationship', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Staycation',
                'slug' => 'staycation',
                'description' => 'Turn your home into a luxury retreat for the weekend.',
                'emoji' => '🛋️',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 8,
                'display_order' => 16,
                'is_seasonal' => false,
                'artwork' => 'staycation',
                'questions' => [
                    [
                        'title' => 'What\'s your staycation style?',
                        'description' => 'Choose how you want to spend your time at home.',
                        'emoji' => '🏠',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🛁', 'title' => 'Spa & pamper day', 'value' => 'spa', 'display_order' => 1],
                            ['emoji' => '🎬', 'title' => 'Movie marathon', 'value' => 'movies', 'display_order' => 2],
                            ['emoji' => '🍳', 'title' => 'Cook together all day', 'value' => 'cooking', 'display_order' => 3],
                            ['emoji' => '🎮', 'title' => 'Games & fun', 'value' => 'games', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What food vibe for the staycation?',
                        'description' => 'Pick your culinary adventure.',
                        'emoji' => '🍕',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🍣', 'title' => 'Takeaway feast', 'value' => 'takeaway', 'display_order' => 1],
                            ['emoji' => '👨‍🍳', 'title' => 'Cook a fancy meal', 'value' => 'cook', 'display_order' => 2],
                            ['emoji' => '🧇', 'title' => 'Brunch all day', 'value' => 'brunch', 'display_order' => 3],
                            ['emoji' => '🍿', 'title' => 'Snacks & grazing', 'value' => 'snacks', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Rainy Day',
                'slug' => 'rainy-day',
                'description' => 'Make the most of a cosy rainy day indoors together.',
                'emoji' => '🌧️',
                'status' => 'active',
                'visibility' => 'public',
                'estimated_minutes' => 6,
                'display_order' => 17,
                'is_seasonal' => false,
                'artwork' => 'rainy_day',
                'questions' => [
                    [
                        'title' => 'What\'s your perfect rainy day activity?',
                        'description' => 'Choose how to spend the day.',
                        'emoji' => '☔',
                        'type' => 'single_choice',
                        'display_order' => 1,
                        'required' => true,
                        'options' => [
                            ['emoji' => '📚', 'title' => 'Read together', 'value' => 'reading', 'display_order' => 1],
                            ['emoji' => '🎬', 'title' => 'Movie day', 'value' => 'movies', 'display_order' => 2],
                            ['emoji' => '🎲', 'title' => 'Board games', 'value' => 'games', 'display_order' => 3],
                            ['emoji' => '🍵', 'title' => 'Tea & conversation', 'value' => 'conversation', 'display_order' => 4],
                        ],
                    ],
                    [
                        'title' => 'What\'s the comfort food of choice?',
                        'description' => 'Pick your rainy day treat.',
                        'emoji' => '🍲',
                        'type' => 'single_choice',
                        'display_order' => 2,
                        'required' => true,
                        'options' => [
                            ['emoji' => '🍜', 'title' => 'Soup & bread', 'value' => 'soup', 'display_order' => 1],
                            ['emoji' => '🍕', 'title' => 'Pizza', 'value' => 'pizza', 'display_order' => 2],
                            ['emoji' => '🍫', 'title' => 'Hot chocolate & cake', 'value' => 'chocolate', 'display_order' => 3],
                            ['emoji' => '🥘', 'title' => 'Slow-cooked comfort food', 'value' => 'comfort', 'display_order' => 4],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($questionnaires as $data) {
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
}
