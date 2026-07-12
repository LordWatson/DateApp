<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $alex = User::create([
            'name' => 'Alex Watson',
            'email' => 'squatty.watson@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => Gender::Male,
            'date_of_birth' => '1990-06-15',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'push_notifications' => true,
            'dark_mode' => false,
        ]);

        $eliza = User::create([
            'name' => 'Eliza Watson',
            'email' => 'alexander.watson.work@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => Gender::Female,
            'date_of_birth' => '1992-03-22',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'push_notifications' => true,
            'dark_mode' => false,
        ]);

        $alex->update(['partner_id' => $eliza->id]);
        $eliza->update(['partner_id' => $alex->id]);

        $this->call([
            QuestionnaireSeeder::class,
            ChallengeSeeder::class,
        ]);
    }
}
