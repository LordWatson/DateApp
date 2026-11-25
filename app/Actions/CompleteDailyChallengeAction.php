<?php

namespace App\Actions;

use App\Events\ChallengeCompleted;
use App\Jobs\AchievementEvaluationJob;
use App\Models\DailyChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteDailyChallengeAction
{
    /**
     * Marks today's challenge as done for the user. Returns false when there is
     * no challenge today or the user had already completed it.
     */
    public function execute(User $user): bool
    {
        $daily = DailyChallenge::forToday();

        if (! $daily) {
            return false;
        }

        // The unique index makes a repeat (or a double tap) a no-op.
        $inserted = DB::table('daily_challenge_completions')->insertOrIgnore([
            'daily_challenge_id' => $daily->id,
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        if ($inserted === 0) {
            return false;
        }

        $this->extendCoupleStreak($user, $daily);

        ChallengeCompleted::dispatch($user, $daily->challenge);

        return true;
    }

    /**
     * The couple streak grows on a day both partners complete the challenge.
     * It is stored on both users and written together.
     */
    private function extendCoupleStreak(User $user, DailyChallenge $daily): void
    {
        if ($user->partner_id === null || $user->isConnectionPaused() || ! $daily->completedUserIds()->contains($user->partner_id)) {
            return;
        }

        $today = $daily->date->toDateString();
        $yesterday = $daily->date->subDay()->toDateString();

        $extended = DB::transaction(function () use ($user, $today, $yesterday): bool {
            $couple = User::whereKey([$user->id, $user->partner_id])->orderBy('id')->lockForUpdate()->get();
            $last = $couple->first()?->challenge_streak_date?->toDateString();

            // Already counted: the other partner's request got here first.
            if ($couple->count() !== 2 || $last === $today) {
                return false;
            }

            $streak = $last === $yesterday ? $couple->first()->challenge_streak + 1 : 1;

            foreach ($couple as $person) {
                $person->update([
                    'challenge_streak' => $streak,
                    'challenge_streak_date' => $today,
                    'longest_challenge_streak' => max($person->longest_challenge_streak, $streak),
                ]);
            }

            return true;
        });

        // The event only re-checks the person who pressed Done.
        if ($extended && $user->partner) {
            AchievementEvaluationJob::dispatch($user->partner);
        }
    }
}
