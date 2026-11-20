<?php

namespace App\Mail;

use App\Models\BillingDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationAcceptanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BillingDocument $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Quotation '.$this->document->number.' accepted');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation-accepted');
    }
}
