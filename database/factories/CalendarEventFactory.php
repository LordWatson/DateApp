<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['date_night', 'anniversary', 'birthday', 'custom']),
            'emoji' => '📅',
            'date' => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'time' => null,
            'location' => $this->faker->city(),
            'notes' => null,
            'colour' => '#EC4899',
            'reminder' => null,
            'reminder_sent' => false,
        ];
    }
}
