<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $invitationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your email and set up your OMG Computer account');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-invitation',
            with: [
                'recipientName' => $this->recipientName,
                'invitationUrl' => $this->invitationUrl,
            ],
        );
    }
}
