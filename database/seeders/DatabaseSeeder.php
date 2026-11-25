<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Reference data only, safe to run on any environment and to run again.
     * The demo couple (known password, super administrator) is local only.
     */
    public function run(): void
    {
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
            DemoCoupleSeeder::class,
        ]);
    }
}
