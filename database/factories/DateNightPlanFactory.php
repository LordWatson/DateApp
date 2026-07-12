<?php

namespace Database\Factories;

use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DateNightPlan>
 */
class DateNightPlanFactory extends Factory
{
    protected $model = DateNightPlan::class;

    public function definition(): array
    {
        return [
            'questionnaire_id' => Questionnaire::factory(),
            'partner_one_response_id' => Response::factory(),
            'partner_two_response_id' => Response::factory(),
            'date_night_theme_id' => null,
            'compatibility_score' => $this->faker->numberBetween(40, 100),
            'theme' => $this->faker->randomElement(['Cozy Night In', 'Candlelit Evening', 'Movie Marathon', 'Game Night']),
            'theme_emoji' => $this->faker->randomElement(['❤️', '🍷', '🎬', '🎲']),
            'summary' => $this->faker->paragraph(),
            'meal_suggestion' => $this->faker->sentence(),
            'drink_suggestion' => $this->faker->sentence(),
            'music_vibe' => $this->faker->sentence(),
            'atmosphere' => $this->faker->sentence(),
            'activity' => $this->faker->sentence(),
            'conversation_prompt' => $this->faker->sentence().'?',
            'romantic_challenge' => $this->faker->sentence(),
            'is_favourite' => false,
        ];
    }

    public function favourite(): static
    {
        return $this->state(['is_favourite' => true]);
    }
}
