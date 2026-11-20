<?php

namespace App\Mail;

use App\Models\BillingDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Lang;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BillingDocument $document,
        public string $pdfContent,
        public string $pdfFilename,
        public bool $preview = false,
        string $locale = 'en',
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: Lang::get(
            'billing.email.invoice_subject',
            ['number' => $this->document->number],
            $this->locale,
        ));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
