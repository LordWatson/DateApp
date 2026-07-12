<?php

namespace App\Console\Commands;

use App\Jobs\RefreshRelationshipStatisticsJob;
use App\Models\User;
use Illuminate\Console\Command;

final class RefreshStatisticsCommand extends Command
{
    protected $signature = 'datenight:refresh-statistics';

    protected $description = 'Refresh relationship statistics for all users';

    public function handle(): int
    {
        $users = User::whereNotNull('partner_id')->get();

        foreach ($users as $user) {
            RefreshRelationshipStatisticsJob::dispatch($user);
        }

        $this->info("Dispatched statistics refresh for {$users->count()} users.");

        return self::SUCCESS;
    }
}
