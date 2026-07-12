<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'gender' => fake()->randomElement(Gender::cases()),
            'partner_id' => null,
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'avatar' => null,
            'timezone' => fake()->randomElement(['UTC', 'Europe/London', 'America/New_York', 'Australia/Sydney']),
            'last_completed_questionnaire_at' => null,
            'current_streak' => 0,
            'longest_streak' => 0,
            'monthly_completion_count' => 0,
            'email_notifications' => true,
            'push_notifications' => true,
            'dark_mode' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function withStreak(int $streak): static
    {
        return $this->state(fn (array $attributes) => [
            'current_streak' => $streak,
            'longest_streak' => max($streak, $attributes['longest_streak'] ?? 0),
            'last_completed_questionnaire_at' => now(),
        ]);
    }
}
