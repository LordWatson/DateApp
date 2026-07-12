<?php

namespace App\Mail;

use App\Models\PartnerInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PartnerInvitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💕 '.$this->invitation->sender->display_name.' wants to connect on Date Night',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-invitation',
            with: [
                'senderName' => $this->invitation->sender->display_name ?? $this->invitation->sender->name,
                'acceptUrl' => route('onboarding.accept-invite', ['token' => $this->invitation->token]),
                'expiresAt' => $this->invitation->expires_at->format('F j, Y'),
            ],
        );
    }
}
