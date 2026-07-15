<?php

namespace App\Mail;

use App\Models\Moment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MomentCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly User $creator,
        public readonly Moment $moment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📸 '.($this->creator->display_name ?? $this->creator->name).' saved a new moment!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.moment-created',
            with: [
                'recipientName' => $this->recipient->display_name ?? $this->recipient->name,
                'creatorName' => $this->creator->display_name ?? $this->creator->name,
                'momentTitle' => $this->moment->title,
                'momentsUrl' => route('moments.index'),
            ],
        );
    }
}
