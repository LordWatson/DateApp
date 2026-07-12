<?php

namespace App\Console\Commands;

use App\Jobs\DailyChallengeSelectionJob;
use Illuminate\Console\Command;

final class SelectDailyChallengeCommand extends Command
{
    protected $signature = 'datenight:select-daily-challenge';

    protected $description = 'Select the daily challenge for today';

    public function handle(): int
    {
        DailyChallengeSelectionJob::dispatch();
        $this->info('Daily challenge selection dispatched.');

        return self::SUCCESS;
    }
}
