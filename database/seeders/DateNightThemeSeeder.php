<?php

namespace Database\Seeders;

use App\Models\DateNightTheme;
use Illuminate\Database\Seeder;

class DateNightThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            ['name' => 'Cozy Night In', 'emoji' => '❤️', 'description' => 'a warm, intimate evening at home with soft lighting and comfort', 'colour' => '#EC4899'],
            ['name' => 'Candlelit Evening', 'emoji' => '🍷', 'description' => 'a romantic candlelit atmosphere with wine and soft music', 'colour' => '#9333EA'],
            ['name' => 'Movie Marathon', 'emoji' => '🎬', 'description' => 'a cosy film night with snacks, blankets, and your favourite movies', 'colour' => '#6366F1'],
            ['name' => 'Homemade Pizza Night', 'emoji' => '🍕', 'description' => 'a fun cooking experience making pizza together from scratch', 'colour' => '#F59E0B'],
            ['name' => 'Rainy Day Romance', 'emoji' => '🌧️', 'description' => 'a peaceful indoor evening listening to the rain with hot drinks', 'colour' => '#3B82F6'],
            ['name' => 'Sunset Walk', 'emoji' => '🌅', 'description' => 'a gentle outdoor walk together to catch the golden hour', 'colour' => '#F97316'],
            ['name' => 'Game Night', 'emoji' => '🎲', 'description' => 'a playful evening of board games, card games, and friendly competition', 'colour' => '#10B981'],
            ['name' => 'Picnic Together', 'emoji' => '🧺', 'description' => 'a relaxed outdoor picnic with good food and great company', 'colour' => '#84CC16'],
            ['name' => 'Anniversary Style', 'emoji' => '✨', 'description' => 'a special, elevated evening celebrating your connection', 'colour' => '#EC4899'],
            ['name' => 'Coffee & Conversation', 'emoji' => '☕', 'description' => 'a slow, meaningful evening of deep conversation over warm drinks', 'colour' => '#92400E'],
            ['name' => 'Music & Memories', 'emoji' => '🎵', 'description' => 'an evening built around music, dancing, and shared memories', 'colour' => '#7C3AED'],
            ['name' => 'Slow Evening', 'emoji' => '🕯️', 'description' => 'a deliberately unhurried evening with no agenda — just being together', 'colour' => '#6B7280'],
        ];

        foreach ($themes as $theme) {
            DateNightTheme::firstOrCreate(
                ['name' => $theme['name']],
                array_merge($theme, ['active' => true]),
            );
        }
    }
}
