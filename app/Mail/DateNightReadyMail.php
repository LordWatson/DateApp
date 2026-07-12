<?php

namespace App\Mail;

use App\Models\DateNightPlan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DateNightReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly DateNightPlan $plan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❤️ Your Date Night Is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.date-night-ready',
            with: [
                'recipientName' => $this->recipient->display_name ?? $this->recipient->name,
                'plan' => $this->plan,
                'resultsUrl' => route('date-night.show', $this->plan->id),
            ],
        );
    }
}
