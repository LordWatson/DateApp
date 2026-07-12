<?php

namespace App\Jobs;

use App\Mail\DateNightReadyMail;
use App\Models\DateNightPlan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendSummaryEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly User $user,
        public readonly DateNightPlan $plan,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        if (! $this->user->email_notifications) {
            return;
        }

        Mail::to($this->user->email)->send(new DateNightReadyMail($this->user, $this->plan));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendSummaryEmailJob failed', [
            'user' => $this->user->id,
            'plan' => $this->plan->id,
            'error' => $e->getMessage(),
        ]);
    }
}
