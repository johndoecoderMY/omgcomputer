<?php

namespace App\Services;

use App\Models\BillingDocument;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuestInvoicePaymentLinkService
{
    public function activeUrl(BillingDocument $document, ?string $locale = null): ?string
    {
        try {
            $token = $document->guest_payment_token_encrypted;
        } catch (DecryptException) {
            return null;
        }

        if (! $this->isPayableInvoice($document)
            || ! $document->guest_payment_link_expires_at?->isFuture()
            || ! is_string($token)
            || ! is_string($document->guest_payment_token_hash)
            || ! hash_equals($document->guest_payment_token_hash, hash('sha256', $token))) {
            return null;
        }

        $parameters = ['document' => $document, 'token' => $token];
        if ($locale !== null) {
            $parameters['locale'] = $locale;
        }

        return route('billing.guest.invoice', $parameters);
    }

    public function getOrCreate(BillingDocument $document, ?string $locale = null): string
    {
        return DB::transaction(function () use ($document, $locale): string {
            $lockedDocument = BillingDocument::query()
                ->lockForUpdate()
                ->findOrFail($document->id);
            abort_unless($this->isPayableInvoice($lockedDocument), 422, 'This invoice is not eligible for an online payment link.');

            return $this->activeUrl($lockedDocument, $locale)
                ?? $this->replace($lockedDocument, $locale);
        });
    }

    public function isPayableInvoice(BillingDocument $document): bool
    {
        return $document->type === 'invoice'
            && in_array($document->status, ['issued', 'partial', 'part_paid'], true)
            && $document->balance > 0;
    }

    public function replace(BillingDocument $document, ?string $locale = null): string
    {
        $token = Str::random(64);
        $document->forceFill([
            'guest_payment_token_hash' => hash('sha256', $token),
            'guest_payment_token_encrypted' => $token,
            'guest_payment_link_expires_at' => now()->addDays(7),
        ])->save();

        $parameters = ['document' => $document, 'token' => $token];
        if ($locale !== null) {
            $parameters['locale'] = $locale;
        }

        return route('billing.guest.invoice', $parameters);
    }

}
