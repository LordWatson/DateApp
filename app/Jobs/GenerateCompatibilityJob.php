<?php

namespace App\Jobs;

use App\Events\CompatibilityCalculated;
use App\Models\Questionnaire;
use App\Models\User;
use App\Services\CompatibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateCompatibilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly User $userOne,
        public readonly User $userTwo,
        public readonly Questionnaire $questionnaire,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(CompatibilityService $service): void
    {
        $result = $service->calculate($this->userOne, $this->questionnaire);

        if ($result === null) {
            Log::warning('GenerateCompatibilityJob: no result', [
                'user_one' => $this->userOne->id,
                'questionnaire' => $this->questionnaire->id,
            ]);

            return;
        }

        CompatibilityCalculated::dispatch(
            $this->userOne,
            $this->userTwo,
            $this->questionnaire,
            $result['percentage'],
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateCompatibilityJob failed', [
            'user_one' => $this->userOne->id,
            'questionnaire' => $this->questionnaire->id,
            'error' => $e->getMessage(),
        ]);
    }
}
