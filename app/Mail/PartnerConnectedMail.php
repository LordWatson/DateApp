<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerConnectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly User $partner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❤️ You\'re connected on Date Night!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-connected',
            with: [
                'userName' => $this->user->display_name ?? $this->user->name,
                'partnerName' => $this->partner->display_name ?? $this->partner->name,
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
