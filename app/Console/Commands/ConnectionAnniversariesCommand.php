<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Jobs\AchievementEvaluationJob;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Console\Command;

final class ConnectionAnniversariesCommand extends Command
{
    protected $signature = 'datenight:connection-anniversaries';

    protected $description = 'Tell couples when it is the anniversary of the day they connected';

    public function handle(): int
    {
        // shortcut: the day is the UTC day, not each user's own; switch to an hourly per-timezone run if that matters.
        $today = now();

        $users = User::with('partner')
            ->whereNotNull('partner_id')
            ->whereMonth('connected_at', $today->month)
            ->whereDay('connected_at', $today->day)
            ->whereYear('connected_at', '<', $today->year)
            ->get();

        foreach ($users as $user) {
            $years = $today->year - $user->connected_at->year;
            $partnerName = $user->partner->display_name ?? $user->partner->name;

            AppNotification::create([
                'user_id' => $user->id,
                'type' => NotificationType::ConnectionAnniversary,
                'title' => $years === 1 ? 'One year together today' : "{$years} years together today",
                'body' => "You and {$partnerName} connected on this day in {$user->connected_at->year}.",
                'data' => ['years' => $years],
            ]);

            AchievementEvaluationJob::dispatch($user);
        }

        $this->info("Sent {$users->count()} anniversary notifications.");

        return self::SUCCESS;
    }
}
