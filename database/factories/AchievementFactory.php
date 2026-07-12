<?php

namespace Database\Factories;

use App\Enums\AchievementCategory;
use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'emoji' => '🏆',
            'category' => AchievementCategory::Questionnaires,
            'unlock_condition' => 'streak_days',
            'unlock_value' => fake()->numberBetween(1, 30),
            'points' => fake()->numberBetween(10, 100),
            'hidden' => false,
        ];
    }
}
