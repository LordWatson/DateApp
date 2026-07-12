<?php

namespace Database\Factories;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Questionnaire>
 */
class QuestionnaireFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'emoji' => fake()->randomElement(['❤️', '🔥', '✨', '💕', '🌙', '🎉']),
            'cover_image' => null,
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
            'estimated_minutes' => fake()->numberBetween(3, 15),
            'display_order' => fake()->numberBetween(0, 100),
            'active_from' => null,
            'active_until' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuestionnaireStatus::Draft,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuestionnaireStatus::Archived,
        ]);
    }
}
