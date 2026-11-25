<?php

namespace App\Console\Commands;

use App\Models\Moment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class MoveUploadsCommand extends Command
{
    protected $signature = 'datenight:move-uploads {--from=public : The disk the photos are on now}';

    protected $description = 'Move moment photos from an old disk to the uploads disk (safe to run again)';

    public function handle(): int
    {
        $from = Storage::disk($this->option('from'));
        $to = Storage::disk(config('filesystems.uploads'));
        $moved = 0;

        if ($this->option('from') === config('filesystems.uploads')) {
            $this->info('The uploads disk is the same disk. Nothing to move.');

            return self::SUCCESS;
        }

        foreach (Moment::pluck('photos')->flatten()->filter() as $path) {
            if (! $from->exists($path)) {
                continue;
            }

            // Copy first and delete only after the copy succeeded, so a failure loses nothing.
            if (! $to->writeStream($path, $from->readStream($path))) {
                $this->error("Could not copy {$path}.");

                return self::FAILURE;
            }

            $from->delete($path);
            $moved++;
        }

        $this->info("Moved {$moved} moment photos.");

        return self::SUCCESS;
    }
}
