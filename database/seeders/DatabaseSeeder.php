<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\Role;
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
            'name' => 'Linda',
            'email' => 'squatty.watson@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => Gender::Male,
            'date_of_birth' => '1993-10-16',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'push_notifications' => true,
            'dark_mode' => false,
        ]);

        /*$eliza = User::create([
            'name' => 'Fanny',
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
        $eliza->update(['partner_id' => $alex->id]);*/

        $this->call([
            RoleSeeder::class,
            FeatureFlagSeeder::class,
            SystemSettingSeeder::class,
            SoloDatePlannerQuestionnaireSeeder::class,
            QuestionnaireSeeder::class,
            SeasonalQuestionnaireSeeder::class,
            IntimacyQuestionnaireSeeder::class,
            IntimacyGameSeeder::class,
            ChallengeSeeder::class,
            DateNightThemeSeeder::class,
            AchievementSeeder::class,
            AiPromptTemplateSeeder::class,
        ]);

        // Assign super admin role to Alex
        $superAdminRole = Role::where('name', UserRole::SuperAdministrator->value)->first();
        $alex->update(['role_id' => $superAdminRole?->id, 'onboarding_completed' => true]);
        // $eliza->update(['onboarding_completed' => true]);
    }
}
