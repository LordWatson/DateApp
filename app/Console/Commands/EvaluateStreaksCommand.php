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
        $broken = User::whereNotNull('last_completed_questionnaire_at')
            ->where('current_streak', '>', 0)
            ->where('last_completed_questionnaire_at', '<', now()->subDays(1)->startOfDay())
            ->get();

        foreach ($broken as $user) {
            $user->update(['current_streak' => 0]);
            Log::info('Streak reset', ['user' => $user->id]);
        }

        $this->info("Evaluated streaks. Reset {$broken->count()} broken streaks.");

        return self::SUCCESS;
    }
}
