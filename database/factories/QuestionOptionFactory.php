<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->singleChoice(),
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'emoji' => fake()->randomElement(['😊', '🔥', '💕', '✨', '🎉', '🌙', '👍', '❤️']),
            'value' => fake()->slug(2),
            'display_order' => fake()->numberBetween(1, 10),
        ];
    }
}
