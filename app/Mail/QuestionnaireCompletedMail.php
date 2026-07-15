<?php

namespace App\Mail;

use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuestionnaireCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly User $sender,
        public readonly Questionnaire $questionnaire,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💕 '.($this->sender->display_name ?? $this->sender->name).' completed the questionnaire!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.questionnaire-completed',
            with: [
                'recipientName' => $this->recipient->display_name ?? $this->recipient->name,
                'senderName' => $this->sender->display_name ?? $this->sender->name,
                'questionnaireTitle' => $this->questionnaire->title,
                'questionnaireUrl' => route('questionnaires.show', $this->questionnaire->slug),
            ],
        );
    }
}
