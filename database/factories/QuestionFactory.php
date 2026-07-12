<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'questionnaire_id' => Questionnaire::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->sentence(),
            'emoji' => fake()->randomElement(['❤️', '🔥', '✨', '💕', '😊', '🎵', '🕯️', '💋', '🤗', '👕', '🍷', '🚫']),
            'type' => fake()->randomElement(QuestionType::cases()),
            'required' => true,
            'minimum_value' => null,
            'maximum_value' => null,
            'display_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::SingleChoice,
            'minimum_value' => null,
            'maximum_value' => null,
        ]);
    }

    public function slider(int $min = 1, int $max = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Slider,
            'minimum_value' => $min,
            'maximum_value' => $max,
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::MultipleChoice,
            'minimum_value' => null,
            'maximum_value' => null,
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Text,
        ]);
    }

    public function textarea(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::TextArea,
        ]);
    }
}
