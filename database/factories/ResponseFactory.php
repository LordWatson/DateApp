<?php

namespace Database\Factories;

use App\Enums\CompletionStatus;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Response>
 */
class ResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'questionnaire_id' => Questionnaire::factory(),
            'status' => CompletionStatus::InProgress,
            'started_at' => now(),
            'completed_at' => null,
            'compatibility_score' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
            'compatibility_score' => fake()->numberBetween(50, 100),
        ]);
    }
}
