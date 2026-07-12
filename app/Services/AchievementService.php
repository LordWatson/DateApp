<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Support\Carbon;

class AchievementService
{
    public function checkAndAward(User $user): array
    {
        $newlyUnlocked = [];
        $achievements = Achievement::all();
        $userAchievementIds = $user->achievements()->pluck('achievement_id')->toArray();

        foreach ($achievements as $achievement) {
            if (in_array($achievement->id, $userAchievementIds)) {
                continue;
            }

            if ($this->conditionMet($user, $achievement)) {
                $user->achievements()->attach($achievement->id, [
                    'unlocked_at' => Carbon::now(),
                ]);
                $newlyUnlocked[] = $achievement;
            }
        }

        return $newlyUnlocked;
    }

    private function conditionMet(User $user, Achievement $achievement): bool
    {
        return match ($achievement->unlock_condition) {
            'questionnaires_completed' => $this->questionnairesCompleted($user) >= $achievement->unlock_value,
            'streak_days' => $user->current_streak >= $achievement->unlock_value,
            'love_notes_sent' => $this->loveNotesSent($user) >= $achievement->unlock_value,
            'calendar_events_created' => $user->calendarEvents()->count() >= $achievement->unlock_value,
            'moments_created' => $user->moments()->count() >= $achievement->unlock_value,
            'perfect_compatibility' => $this->hasPerfectCompatibility($user),
            'average_compatibility' => $this->averageCompatibility($user) >= $achievement->unlock_value,
            'questionnaire_slug' => $this->hasCompletedSlug($user, 'anniversary'),
            default => false,
        };
    }

    private function questionnairesCompleted(User $user): int
    {
        return $user->responses()->where('status', 'completed')->count();
    }

    private function loveNotesSent(User $user): int
    {
        return LoveNote::where('sender_id', $user->id)->count();
    }

    private function hasPerfectCompatibility(User $user): bool
    {
        return DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })->where('compatibility_score', 100)->exists();
    }

    private function averageCompatibility(User $user): float
    {
        return (float) DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })->avg('compatibility_score') ?? 0;
    }

    private function hasCompletedSlug(User $user, string $slug): bool
    {
        return $user->responses()
            ->whereHas('questionnaire', fn ($q) => $q->where('slug', $slug))
            ->where('status', 'completed')
            ->exists();
    }

    public function getUserProgress(User $user): array
    {
        $all = Achievement::where('hidden', false)->get();
        $unlocked = $user->achievements()->pluck('achievement_id')->toArray();
        $totalPoints = $user->achievements()->sum('points');

        return [
            'total' => $all->count(),
            'unlocked' => count($unlocked),
            'points' => $totalPoints,
            'percentage' => $all->count() > 0 ? round((count($unlocked) / $all->count()) * 100) : 0,
        ];
    }
}
