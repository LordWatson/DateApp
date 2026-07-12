<?php

namespace Database\Factories;

use App\Models\DateNightTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DateNightTheme>
 */
class DateNightThemeFactory extends Factory
{
    protected $model = DateNightTheme::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Cozy Night In', 'Candlelit Evening', 'Movie Marathon',
                'Game Night', 'Picnic Together', 'Slow Evening',
            ]),
            'emoji' => $this->faker->randomElement(['❤️', '🍷', '🎬', '🎲', '🧺', '🕯️']),
            'description' => $this->faker->sentence(),
            'colour' => '#EC4899',
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
