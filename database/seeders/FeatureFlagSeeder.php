<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            ['key' => 'ai_generation', 'label' => 'AI Generation', 'description' => 'Enable AI-powered date night plan generation.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'calendar', 'label' => 'Calendar', 'description' => 'Enable the calendar feature for scheduling date nights.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'love_notes', 'label' => 'Love Notes', 'description' => 'Enable love notes between partners.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'achievements', 'label' => 'Achievements', 'description' => 'Enable the achievements and rewards system.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'moments', 'label' => 'Moments', 'description' => 'Enable the moments photo journal feature.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'seasonal_content', 'label' => 'Seasonal Content', 'description' => 'Enable seasonal questionnaires and challenges.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'insights', 'label' => 'Insights', 'description' => 'Enable relationship insights and analytics for users.', 'enabled' => true, 'group' => 'features'],
            ['key' => 'beta_features', 'label' => 'Beta Features', 'description' => 'Enable experimental beta features for all users.', 'enabled' => false, 'group' => 'beta'],
            ['key' => 'registration', 'label' => 'Registration', 'description' => 'Allow new users to register.', 'enabled' => true, 'group' => 'system'],
            ['key' => 'maintenance_mode', 'label' => 'Maintenance Mode', 'description' => 'Put the application in maintenance mode.', 'enabled' => false, 'group' => 'system'],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::updateOrCreate(['key' => $flag['key']], $flag);
        }
    }
}
