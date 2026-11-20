<?php

namespace App\Mail;

use App\Models\BillingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Lang;

class BillingReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BillingPayment $payment,
        public string $pdfContent,
        public bool $preview = false,
        string $locale = 'en',
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: Lang::get(
            'billing.email.receipt_subject',
            ['number' => $this->payment->receipt_number],
            $this->locale,
        ));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.billing-receipt');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->pdfContent,
                strtolower($this->payment->receipt_number).'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
