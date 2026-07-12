<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CleanTemporaryExportsCommand extends Command
{
    protected $signature = 'datenight:clean-exports';

    protected $description = 'Clean temporary export files older than 24 hours';

    public function handle(): int
    {
        $files = Storage::files('exports');
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);

            if ($lastModified < now()->subDay()->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }

        $this->info("Cleaned {$deleted} temporary export files.");

        return self::SUCCESS;
    }
}
