<?php

namespace Database\Factories;

use App\Models\Moment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Moment>
 */
class MomentFactory extends Factory
{
    protected $model = Moment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date_night_plan_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'mood' => $this->faker->randomElement(['😍', '😊', '🥰', '😂', '😌']),
            'photo' => null,
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_favourite' => false,
            'private_notes' => null,
            'tags' => [],
        ];
    }
}
