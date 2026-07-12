<?php

namespace App\Jobs;

use App\Models\Challenge;
use App\Models\DailyChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class DailyChallengeSelectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $today = now()->toDateString();

        if (DailyChallenge::whereDate('date', $today)->exists()) {
            return;
        }

        $challenge = Challenge::inRandomOrder()->first();

        if (! $challenge) {
            Log::warning('DailyChallengeSelectionJob: no challenges available');

            return;
        }

        DailyChallenge::create([
            'challenge_id' => $challenge->id,
            'date' => $today,
        ]);

        Log::info('DailyChallengeSelectionJob: challenge selected', [
            'challenge' => $challenge->id,
            'date' => $today,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DailyChallengeSelectionJob failed', ['error' => $e->getMessage()]);
    }
}
