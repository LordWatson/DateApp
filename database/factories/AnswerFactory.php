<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Response;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'response_id' => Response::factory(),
            'question_id' => Question::factory()->singleChoice(),
            'question_option_id' => null,
            'value' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
