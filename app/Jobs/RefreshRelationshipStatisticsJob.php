<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class RefreshRelationshipStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly User $user,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        Cache::forget("dashboard:{$this->user->id}");
        Cache::forget("compatibility:{$this->user->id}");

        if ($this->user->partner_id) {
            Cache::forget("dashboard:{$this->user->partner_id}");
            Cache::forget("compatibility:{$this->user->partner_id}");
        }

        Log::info('RefreshRelationshipStatisticsJob completed', ['user' => $this->user->id]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RefreshRelationshipStatisticsJob failed', [
            'user' => $this->user->id,
            'error' => $e->getMessage(),
        ]);
    }
}
