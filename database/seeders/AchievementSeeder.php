<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'First Date Night',
                'description' => 'Complete your very first questionnaire together.',
                'emoji' => '❤️',
                'category' => 'questionnaires',
                'points' => 10,
                'hidden' => false,
                'unlock_condition' => 'questionnaires_completed',
                'unlock_value' => 1,
            ],
            [
                'name' => '7 Day Streak',
                'description' => 'Complete questionnaires 7 days in a row.',
                'emoji' => '🔥',
                'category' => 'streaks',
                'points' => 25,
                'hidden' => false,
                'unlock_condition' => 'streak_days',
                'unlock_value' => 7,
            ],
            [
                'name' => '30 Day Streak',
                'description' => 'Complete questionnaires 30 days in a row.',
                'emoji' => '🔥',
                'category' => 'streaks',
                'points' => 100,
                'hidden' => false,
                'unlock_condition' => 'streak_days',
                'unlock_value' => 30,
            ],
            [
                'name' => 'First Love Note',
                'description' => 'Send your first love note to your partner.',
                'emoji' => '💌',
                'category' => 'love_notes',
                'points' => 10,
                'hidden' => false,
                'unlock_condition' => 'love_notes_sent',
                'unlock_value' => 1,
            ],
            [
                'name' => 'First Calendar Event',
                'description' => 'Create your first shared calendar event.',
                'emoji' => '📅',
                'category' => 'calendar',
                'points' => 10,
                'hidden' => false,
                'unlock_condition' => 'calendar_events_created',
                'unlock_value' => 1,
            ],
            [
                'name' => 'First Moment',
                'description' => 'Capture your first special moment together.',
                'emoji' => '📖',
                'category' => 'moments',
                'points' => 10,
                'hidden' => false,
                'unlock_condition' => 'moments_created',
                'unlock_value' => 1,
            ],
            [
                'name' => 'Ten Questionnaires',
                'description' => 'Complete ten questionnaires together.',
                'emoji' => '🎯',
                'category' => 'questionnaires',
                'points' => 50,
                'hidden' => false,
                'unlock_condition' => 'questionnaires_completed',
                'unlock_value' => 10,
            ],
            [
                'name' => 'Perfect Match',
                'description' => 'Achieve 100% compatibility on a questionnaire.',
                'emoji' => '🏆',
                'category' => 'compatibility',
                'points' => 150,
                'hidden' => false,
                'unlock_condition' => 'perfect_compatibility',
                'unlock_value' => 1,
            ],
            [
                'name' => 'Anniversary Planner',
                'description' => 'Complete the Anniversary questionnaire.',
                'emoji' => '🎉',
                'category' => 'milestones',
                'points' => 30,
                'hidden' => false,
                'unlock_condition' => 'questionnaire_slug',
                'unlock_value' => 1,
            ],
            [
                'name' => 'Romantic Expert',
                'description' => 'Complete 25 questionnaires together.',
                'emoji' => '🌹',
                'category' => 'romance',
                'points' => 200,
                'hidden' => false,
                'unlock_condition' => 'questionnaires_completed',
                'unlock_value' => 25,
            ],
            [
                'name' => 'Love Letter Writer',
                'description' => 'Send 10 love notes to your partner.',
                'emoji' => '✉️',
                'category' => 'love_notes',
                'points' => 40,
                'hidden' => false,
                'unlock_condition' => 'love_notes_sent',
                'unlock_value' => 10,
            ],
            [
                'name' => 'Memory Keeper',
                'description' => 'Create 10 moments together.',
                'emoji' => '📸',
                'category' => 'moments',
                'points' => 40,
                'hidden' => false,
                'unlock_condition' => 'moments_created',
                'unlock_value' => 10,
            ],
            [
                'name' => 'Social Butterfly',
                'description' => 'Create 5 calendar events.',
                'emoji' => '🦋',
                'category' => 'calendar',
                'points' => 25,
                'hidden' => false,
                'unlock_condition' => 'calendar_events_created',
                'unlock_value' => 5,
            ],
            [
                'name' => 'Compatibility King',
                'description' => 'Average compatibility score above 80%.',
                'emoji' => '👑',
                'category' => 'compatibility',
                'points' => 75,
                'hidden' => false,
                'unlock_condition' => 'average_compatibility',
                'unlock_value' => 80,
            ],
            [
                'name' => 'Secret Admirer',
                'description' => 'Send 50 love notes.',
                'emoji' => '💝',
                'category' => 'love_notes',
                'points' => 100,
                'hidden' => true,
                'unlock_condition' => 'love_notes_sent',
                'unlock_value' => 50,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(
                ['unlock_condition' => $achievement['unlock_condition'], 'name' => $achievement['name']],
                $achievement
            );
        }
    }
}
