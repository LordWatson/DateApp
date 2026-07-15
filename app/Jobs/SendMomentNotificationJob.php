<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Mail\MomentCreatedMail;
use App\Models\AppNotification;
use App\Models\Moment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendMomentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly User $recipient,
        public readonly Moment $moment,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $creator = $this->moment->user;

        AppNotification::create([
            'user_id' => $this->recipient->id,
            'type' => NotificationType::MomentCreated,
            'title' => '📸 A new moment was saved!',
            'body' => ($creator->display_name ?? $creator->name).' saved a new moment: '.$this->moment->title,
            'data' => ['moment_id' => $this->moment->id],
        ]);

        Mail::to($this->recipient->email)
            ->queue(new MomentCreatedMail($this->recipient, $creator, $this->moment));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendMomentNotificationJob failed', [
            'recipient' => $this->recipient->id,
            'moment' => $this->moment->id,
            'error' => $e->getMessage(),
        ]);
    }
}
