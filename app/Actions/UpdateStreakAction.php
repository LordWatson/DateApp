<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;

class UpdateStreakAction
{
    public function execute(User $user): void
    {
        $now = Carbon::now();
        $last = $user->last_completed_questionnaire_at;

        $isConsecutiveDay = $last && $last->isYesterday();
        $isSameDay = $last && $last->isToday();

        $newStreak = match (true) {
            $isSameDay => $user->current_streak,
            $isConsecutiveDay => $user->current_streak + 1,
            default => 1,
        };

        $user->update([
            'current_streak' => $newStreak,
            'longest_streak' => max($user->longest_streak, $newStreak),
            'last_completed_questionnaire_at' => $now,
            'monthly_completion_count' => $isSameDay
                ? $user->monthly_completion_count
                : $user->monthly_completion_count + 1,
        ]);
    }
}
