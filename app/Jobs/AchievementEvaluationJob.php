<?php

namespace App\Jobs;

use App\Events\AchievementUnlocked;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AchievementEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly User $user,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(AchievementService $service): void
    {
        $unlocked = $service->checkAndAward($this->user);

        foreach ($unlocked as $achievement) {
            AchievementUnlocked::dispatch($this->user, $achievement);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AchievementEvaluationJob failed', [
            'user' => $this->user->id,
            'error' => $e->getMessage(),
        ]);
    }
}
