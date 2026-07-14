<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoveNoteReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly User $sender,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💌 You have a new Love Note from '.($this->sender->display_name ?? $this->sender->name),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.love-note-received',
            with: [
                'recipientName' => $this->recipient->display_name ?? $this->recipient->name,
                'senderName' => $this->sender->display_name ?? $this->sender->name,
                'loveNotesUrl' => route('love-notes.index'),
            ],
        );
    }
}
