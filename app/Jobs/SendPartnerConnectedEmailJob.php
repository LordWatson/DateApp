<?php

namespace App\Jobs;

use App\Mail\PartnerConnectedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendPartnerConnectedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly User $user,
        public readonly User $partner,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        if (! $this->user->email_notifications) {
            return;
        }

        Mail::to($this->user->email)->send(new PartnerConnectedMail($this->user, $this->partner));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendPartnerConnectedEmailJob failed', [
            'user' => $this->user->id,
            'error' => $e->getMessage(),
        ]);
    }
}
