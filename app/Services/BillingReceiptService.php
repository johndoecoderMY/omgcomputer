<?php

namespace App\Services;

use App\Mail\BillingReceiptMail;
use App\Models\BillingPayment;
use App\Models\CompanyProfile;
use App\Models\CustomerMessageTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mpdf\Mpdf;
use Throwable;

class BillingReceiptService
{
    public function emailReceipt(
        BillingPayment $payment,
        ?string $locale = null,
        ?string $recipientEmail = null,
        bool $resend = false,
    ): bool
    {
        $payment->loadMissing(['document', 'user']);
        if ($payment->receipt_emailed_at && ! $resend) {
            return true;
        }

        $email = filter_var(trim((string) ($recipientEmail ?? $payment->document->customer_email)), FILTER_VALIDATE_EMAIL);
        if (! $email && $recipientEmail === null && $payment->user?->hasRole('customer')) {
            $email = filter_var(trim((string) $payment->user->email), FILTER_VALIDATE_EMAIL);
        }

        if (! $email) {
            Log::warning('Billing receipt email could not be sent because no valid customer email is available.', [
                'payment_id' => $payment->id,
                'document_id' => $payment->billing_document_id,
            ]);

            return false;
        }

        $locale = $this->supportedLocale($locale);
        try {
            app(MailSettingsService::class)->apply();
            Mail::to($email)->send(new BillingReceiptMail(
                $payment,
                $this->renderPdf($payment, $locale),
                false,
                $locale,
            ));

            $payment->forceFill(['receipt_emailed_at' => now()])->save();

            return true;
        } catch (Throwable $exception) {
            Log::warning('Billing receipt email could not be sent.', [
                'payment_id' => $payment->id,
                'document_id' => $payment->billing_document_id,
                'email' => $email,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function renderPdf(BillingPayment $payment, ?string $locale = null): string
    {
        $payment->loadMissing('document');
        $locale = $this->supportedLocale($locale);
        $companyProfile = CompanyProfile::current();
        $tempDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDirectory);
        $logoPath = public_path('assets/img/omg/logoex2.png');
        $logoData = is_file($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : null;
        $originalLocale = app()->getLocale();
        app()->setLocale($locale);
        try {
            $html = view('billing.receipt-pdf', compact(
                'payment',
                'companyProfile',
                'logoData',
            ))->render();
        } finally {
            app()->setLocale($originalLocale);
        }
        $mpdf = new Mpdf([
            'tempDir' => $tempDirectory,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output(strtolower($payment->receipt_number).'.pdf', 'S');
    }

    private function supportedLocale(?string $locale): string
    {
        $locale ??= app()->getLocale();

        return isset(CustomerMessageTemplate::LANGUAGES[$locale]) ? $locale : 'en';
    }
}
