<?php

namespace App\Mail;

use App\Models\RepairTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RepairBillingVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RepairTicket $ticket,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your repair billing verification code - OMG Computer');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.repair-billing-verification');
    }
}
