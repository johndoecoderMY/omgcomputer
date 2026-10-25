<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your OMG Computer membership has been created');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-created',
            with: [
                'recipientName' => $this->recipientName,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
