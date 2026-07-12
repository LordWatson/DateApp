<?php

namespace Database\Factories;

use App\Enums\ChallengeDifficulty;
use App\Models\Challenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Challenge>
 */
class ChallengeFactory extends Factory
{
    public function definition(): array
    {
        $challenges = [
            ['Cook a meal together', '🍳', ChallengeDifficulty::Medium],
            ['Watch the sunset', '🌅', ChallengeDifficulty::Easy],
            ['Dance to one song', '💃', ChallengeDifficulty::Easy],
            ['Give a five minute massage', '🤗', ChallengeDifficulty::Easy],
            ['No phones for one hour', '📵', ChallengeDifficulty::Medium],
            ['Write each other a love note', '💌', ChallengeDifficulty::Easy],
            ['Take a walk together', '🚶', ChallengeDifficulty::Easy],
            ['Bake dessert together', '🍰', ChallengeDifficulty::Medium],
            ['Play a board game', '🎲', ChallengeDifficulty::Easy],
            ['Watch a favourite movie', '🎬', ChallengeDifficulty::Easy],
        ];

        $challenge = fake()->randomElement($challenges);

        return [
            'title' => $challenge[0],
            'description' => null,
            'emoji' => $challenge[1],
            'difficulty' => $challenge[2],
            'active' => true,
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => ChallengeDifficulty::Easy,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => ChallengeDifficulty::Medium,
        ]);
    }

    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => ChallengeDifficulty::Hard,
        ]);
    }
}
