<?php

namespace App\Jobs;

use App\Enums\NotificationCategory;
use App\Mail\QuestionnaireCompletedMail;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendQuestionnaireCompletedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly User $recipient,
        public readonly User $sender,
        public readonly Questionnaire $questionnaire,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        // The listener has already created the in-app notification; this job is only the email.
        if (! $this->recipient->wantsEmail(NotificationCategory::PartnerActivity)) {
            return;
        }

        Mail::to($this->recipient->email)
            ->queue(new QuestionnaireCompletedMail($this->recipient, $this->sender, $this->questionnaire));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendQuestionnaireCompletedNotificationJob failed', [
            'recipient' => $this->recipient->id,
            'sender' => $this->sender->id,
            'questionnaire' => $this->questionnaire->id,
            'error' => $e->getMessage(),
        ]);
    }
}
