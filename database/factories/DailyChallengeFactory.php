<?php

namespace Database\Factories;

use App\Models\Challenge;
use App\Models\DailyChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyChallenge>
 */
class DailyChallengeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'challenge_id' => Challenge::factory(),
            'date' => fake()->unique()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
        ];
    }
}
