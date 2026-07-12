<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'app_name', 'label' => 'Application Name', 'description' => 'The name of the application.', 'value' => 'Date Night', 'type' => 'string', 'group' => 'general'],
            ['key' => 'app_tagline', 'label' => 'Tagline', 'description' => 'Short tagline shown on the welcome page.', 'value' => 'Your perfect evening, planned together.', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_email', 'label' => 'Support Email', 'description' => 'Email address for support enquiries.', 'value' => 'support@datenight.com', 'type' => 'string', 'group' => 'general'],

            // Emails
            ['key' => 'mail_from_name', 'label' => 'Mail From Name', 'description' => 'The sender name for outgoing emails.', 'value' => 'Date Night', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_from_address', 'label' => 'Mail From Address', 'description' => 'The sender email address for outgoing emails.', 'value' => 'hello@datenight.com', 'type' => 'string', 'group' => 'email'],

            // Invitations
            ['key' => 'invitation_expiry_days', 'label' => 'Invitation Expiry (Days)', 'description' => 'Number of days before a partner invitation expires.', 'value' => '7', 'type' => 'integer', 'group' => 'invitations'],

            // Compatibility
            ['key' => 'compatibility_high_threshold', 'label' => 'High Compatibility Threshold (%)', 'description' => 'Score above which compatibility is considered high.', 'value' => '80', 'type' => 'integer', 'group' => 'compatibility'],
            ['key' => 'compatibility_low_threshold', 'label' => 'Low Compatibility Threshold (%)', 'description' => 'Score below which compatibility is considered low.', 'value' => '40', 'type' => 'integer', 'group' => 'compatibility'],

            // Challenges
            ['key' => 'challenge_frequency_days', 'label' => 'Challenge Frequency (Days)', 'description' => 'How often a new challenge is selected.', 'value' => '1', 'type' => 'integer', 'group' => 'challenges'],

            // Colours
            ['key' => 'color_primary', 'label' => 'Primary Colour', 'description' => 'Primary brand colour.', 'value' => '#EC4899', 'type' => 'color', 'group' => 'appearance'],
            ['key' => 'color_secondary', 'label' => 'Secondary Colour', 'description' => 'Secondary brand colour.', 'value' => '#9333EA', 'type' => 'color', 'group' => 'appearance'],
            ['key' => 'color_background', 'label' => 'Background Colour', 'description' => 'Application background colour.', 'value' => '#FFF7FB', 'type' => 'color', 'group' => 'appearance'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
