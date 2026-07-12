<?php

namespace Database\Factories;

use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoveNote>
 */
class LoveNoteFactory extends Factory
{
    protected $model = LoveNote::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'message' => $this->faker->randomElement([
                '❤️ Thinking of you',
                '🥰 Can\'t wait for tonight',
                '🌹 You looked amazing today',
                '☕ Fancy a date night?',
                '✨ Missing you',
            ]),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
