<?php

namespace Database\Factories;

use App\Models\SavedProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedProfile>
 */
class SavedProfileFactory extends Factory
{
    public function definition(): array
    {
        $names = ['Romantic Evening', 'Quickie', 'Movie Night', 'Anniversary', 'Lazy Sunday', 'Date Night', 'Special Occasion'];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($names),
            'emoji' => fake()->randomElement(['❤️', '🔥', '🎬', '🥂', '☀️', '✨', '🌙']),
            'colour' => fake()->randomElement(['#EC4899', '#9333EA', '#10B981', '#F59E0B', '#3B82F6']),
        ];
    }
}
