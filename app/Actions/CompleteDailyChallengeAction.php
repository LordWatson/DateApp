<?php

namespace App\Actions;

use App\Events\ChallengeCompleted;
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

        ChallengeCompleted::dispatch($user, $daily->challenge);

        return true;
    }
}
