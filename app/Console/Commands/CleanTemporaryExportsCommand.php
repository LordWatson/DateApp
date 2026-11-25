<?php

namespace App\Console\Commands;

use App\Jobs\ExportUserDataJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CleanTemporaryExportsCommand extends Command
{
    protected $signature = 'datenight:clean-exports';

    protected $description = 'Remove data export files that have passed their download window';

    public function handle(): int
    {
        $disk = Storage::disk(ExportUserDataJob::DISK);
        $deleted = 0;

        foreach ($disk->files('exports') as $file) {
            if (! ExportUserDataJob::isAvailable($file)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Cleaned {$deleted} temporary export files.");

        return self::SUCCESS;
    }
}
