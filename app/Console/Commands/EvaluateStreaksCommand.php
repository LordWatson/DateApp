<?php

namespace App\Console\Commands;

use App\Actions\UpdateStreakAction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class EvaluateStreaksCommand extends Command
{
    protected $signature = 'datenight:evaluate-streaks';

    protected $description = 'Evaluate and reset broken streaks for all users';

    public function handle(UpdateStreakAction $action): int
    {
        // A streak is broken once a whole day has passed, in the user's own timezone,
        // without a completion. Anything done in the last 24 hours cannot be broken yet.
        $broken = User::whereNotNull('last_completed_questionnaire_at')
            ->where('current_streak', '>', 0)
            ->where('last_completed_questionnaire_at', '<', now()->subDay())
            ->get()
            ->filter(fn (User $user): bool => $user->last_completed_questionnaire_at->lt($user->today()->subDay()));

        foreach ($broken as $user) {
            $user->update(['current_streak' => 0]);
            Log::info('Streak reset', ['user' => $user->id]);
        }

        $this->info("Evaluated streaks. Reset {$broken->count()} broken streaks.");

        return self::SUCCESS;
    }
}
