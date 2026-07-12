<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedProfileAnswer>
 */
class SavedProfileAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'saved_profile_id' => SavedProfile::factory(),
            'question_id' => Question::factory()->singleChoice(),
            'question_option_id' => null,
            'value' => fake()->sentence(),
        ];
    }
}
