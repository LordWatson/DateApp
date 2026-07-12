<?php

namespace App\Jobs;

use App\Mail\PartnerInvitationMail;
use App\Models\PartnerInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendInvitationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly PartnerInvitation $invitation,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        if ($this->invitation->accepted_at !== null) {
            return;
        }

        if ($this->invitation->expires_at->isPast()) {
            return;
        }

        $this->invitation->loadMissing('sender');

        Mail::to($this->invitation->email)->send(new PartnerInvitationMail($this->invitation));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendInvitationEmailJob failed', [
            'invitation' => $this->invitation->id,
            'error' => $e->getMessage(),
        ]);
    }
}
