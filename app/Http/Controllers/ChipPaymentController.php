<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\BillingPaymentAttempt;
use App\Services\ChipCollect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ChipPaymentController extends Controller
{
    public function checkout(BillingDocument $document, Request $request, ChipCollect $chip): RedirectResponse
    {
        $this->authorizeCustomerDocument($request, $document);
        $clientEmail = filter_var($document->customer_email, FILTER_VALIDATE_EMAIL)
            ? $document->customer_email
            : $request->user()->email;

        return $this->startCheckout($document, $chip, $request->user()->id, $clientEmail);
    }

    public function counterCheckout(BillingDocument $document, Request $request, ChipCollect $chip): RedirectResponse
    {
        abort_unless($request->user() && ! $request->user()->hasRole('customer'), 403);

        $clientEmail = filter_var($document->customer_email, FILTER_VALIDATE_EMAIL);
        abort_unless($clientEmail, 422, 'A valid customer email is required for CHIP checkout.');

        return $this->startCheckout($document, $chip, $request->user()->id, $clientEmail);
    }

    public function createGuestPaymentLink(BillingDocument $document, Request $request, ChipCollect $chip): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        abort_unless($this->isPayableInvoice($document) && $chip->isConfigured(), 422, 'This invoice is not eligible for an online payment link.');
        abort_unless(filter_var($document->customer_email, FILTER_VALIDATE_EMAIL), 422, 'A valid customer email is required before creating a payment link.');

        $token = Str::random(64);
        $document->forceFill([
            'guest_payment_token_hash' => hash('sha256', $token),
            'guest_payment_link_expires_at' => now()->addDays(7),
        ])->save();

        return redirect()
            ->route('billing.documents.show', $document)
            ->with('guest_payment_url', route('billing.guest.invoice', [$document, $token]));
    }

    public function guestInvoice(BillingDocument $document, string $token, ChipCollect $chip): Response
    {
        $this->authorizeGuestInvoiceLink($document, $token);

        return response()->view('billing.guest-invoice', [
            'document' => $document->load('items'),
            'chipEnabled' => $chip->isConfigured(),
            'hasValidCustomerEmail' => (bool) filter_var($document->customer_email, FILTER_VALIDATE_EMAIL),
        ])->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }

    public function guestCheckout(BillingDocument $document, string $token, ChipCollect $chip): RedirectResponse
    {
        $this->authorizeGuestInvoiceLink($document, $token);
        abort_unless(filter_var($document->customer_email, FILTER_VALIDATE_EMAIL), 422, 'A valid customer email is required for CHIP checkout.');

        return $this->startCheckout($document, $chip, null, $document->customer_email, true);
    }

    public function callback(BillingPaymentAttempt $attempt, Request $request, ChipCollect $chip): Response
    {
        abort_unless($chip->verifyCallback(
            $request->getContent(),
            $request->header('X-Signature'),
            $attempt->chip_test_mode,
        ), 401);

        $payload = $request->json()->all();
        $purchaseId = $payload['id'] ?? null;
        abort_unless(
            is_string($purchaseId)
            && $attempt->provider_purchase_id !== null
            && hash_equals($attempt->provider_purchase_id, $purchaseId),
            404,
        );

        $purchase = $chip->purchase($attempt->provider_purchase_id, $attempt->chip_test_mode);
        abort_unless(($purchase['id'] ?? null) === $attempt->provider_purchase_id, 502, 'CHIP returned a different purchase.');
        abort_unless(($purchase['status'] ?? null) === 'paid', 409, 'CHIP has not confirmed this purchase as paid.');
        $this->recordVerifiedPayment($attempt);

        return response('', Response::HTTP_OK);
    }

    public function return(BillingPaymentAttempt $attempt, Request $request, ChipCollect $chip): RedirectResponse
    {
        $attempt->load('document');
        $this->authorizeAttemptReturn($request, $attempt);

        if ($attempt->status === 'paid') {
            return redirect()->route('billing.documents.show', $attempt->document)
                ->with('success', 'CHIP confirmed your payment.');
        }

        if ($attempt->provider_purchase_id) {
            $purchase = $chip->purchase($attempt->provider_purchase_id, $attempt->chip_test_mode);
            abort_unless(($purchase['id'] ?? null) === $attempt->provider_purchase_id, 502, 'CHIP returned a different purchase.');
            if (($purchase['status'] ?? null) === 'paid') {
                $this->recordVerifiedPayment($attempt);

                return redirect()->route('billing.documents.show', $attempt->document)
                    ->with('success', 'CHIP confirmed your payment.');
            }
        }

        return redirect()->route('billing.documents.show', $attempt->document)
            ->with('info', 'CHIP has not confirmed this payment yet. The invoice will update when payment is confirmed.');
    }

    public function guestReturn(BillingPaymentAttempt $attempt, ChipCollect $chip): Response
    {
        abort_unless($attempt->user_id === null, 404);
        $attempt->load('document');

        if ($attempt->status !== 'paid' && $attempt->provider_purchase_id) {
            $purchase = $chip->purchase($attempt->provider_purchase_id, $attempt->chip_test_mode);
            abort_unless(($purchase['id'] ?? null) === $attempt->provider_purchase_id, 502, 'CHIP returned a different purchase.');
            if (($purchase['status'] ?? null) === 'paid') {
                $this->recordVerifiedPayment($attempt);
                $attempt->refresh();
            }
        }

        return response()->view('billing.guest-payment-result', [
            'attempt' => $attempt,
        ])->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }

    private function startCheckout(
        BillingDocument $document,
        ChipCollect $chip,
        ?int $userId,
        string $clientEmail,
        bool $guest = false,
    ): RedirectResponse {
        abort_unless($chip->isConfigured() && $this->isPayableInvoice($document), 422, 'This invoice is not eligible for CHIP checkout.');
        abort_unless(filter_var($clientEmail, FILTER_VALIDATE_EMAIL), 422, 'A valid customer email is required for CHIP checkout.');

        $testMode = $chip->isTestMode();
        $attempt = BillingPaymentAttempt::query()->create([
            'uuid' => (string) Str::uuid(),
            'billing_document_id' => $document->id,
            'user_id' => $userId,
            'amount' => $document->balance,
            'chip_test_mode' => $testMode,
            'status' => 'pending',
        ]);

        try {
            $response = $chip->createPurchase([
                'client' => ['email' => $clientEmail],
                'purchase' => [
                    'currency' => config('services.chip.currency', 'MYR'),
                    'products' => [[
                        'name' => 'Invoice '.$document->number,
                        'price' => (int) round((float) $attempt->amount * 100),
                        'quantity' => 1,
                    ]],
                ],
                'brand_id' => $chip->brandId($testMode),
                'reference' => $document->number.'-'.$attempt->uuid,
                'success_redirect' => $guest
                    ? route('billing.guest.chip.return', $attempt->uuid)
                    : route('billing.chip.return', $attempt->uuid),
                'failure_redirect' => $guest
                    ? route('billing.guest.chip.return', $attempt->uuid)
                    : route('billing.chip.return', $attempt->uuid),
                'success_callback' => route('webhooks.chip.collect', $attempt->uuid),
            ], $testMode);
        } catch (Throwable $exception) {
            $attempt->update(['status' => 'failed']);
            throw $exception;
        }

        $purchaseId = $response['id'] ?? null;
        $checkoutUrl = $response['checkout_url'] ?? null;
        $checkoutHost = is_string($checkoutUrl) ? parse_url($checkoutUrl, PHP_URL_HOST) : null;
        if (! (
            is_string($purchaseId)
            && $purchaseId !== ''
            && is_string($checkoutUrl)
            && parse_url($checkoutUrl, PHP_URL_SCHEME) === 'https'
            && is_string($checkoutHost)
            && $checkoutHost === 'gate.chip-in.asia'
        )) {
            $attempt->update(['status' => 'failed']);
            abort(502, 'CHIP returned an invalid purchase response.');
        }

        $attempt->update([
            'provider_purchase_id' => $purchaseId,
            'checkout_url' => $checkoutUrl,
        ]);

        return redirect()->away($checkoutUrl);
    }

    private function isPayableInvoice(BillingDocument $document): bool
    {
        return $document->type === 'invoice'
            && in_array($document->status, ['issued', 'partial', 'part_paid'], true)
            && $document->balance > 0;
    }

    private function authorizeGuestInvoiceLink(BillingDocument $document, string $token): void
    {
        abort_unless(
            $this->isPayableInvoice($document)
            && $document->guest_payment_link_expires_at?->isFuture()
            && is_string($document->guest_payment_token_hash)
            && hash_equals($document->guest_payment_token_hash, hash('sha256', $token)),
            404,
        );
    }

    private function authorizeAttemptReturn(Request $request, BillingPaymentAttempt $attempt): void
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('customer')) {
            $this->authorizeCustomerDocument($request, $attempt->document);

            return;
        }

        abort_unless((int) $attempt->user_id === (int) $user->id, 404);
    }

    private function recordVerifiedPayment(BillingPaymentAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt): void {
            $lockedAttempt = BillingPaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($lockedAttempt->status === 'paid') {
                return;
            }

            $document = BillingDocument::query()->lockForUpdate()->findOrFail($lockedAttempt->billing_document_id);
            abort_unless($document->type === 'invoice' && $document->status !== 'draft', 409);

            $paidAt = now();
            $document->payments()->create([
                'amount' => $lockedAttempt->amount,
                'method' => 'online',
                'reference' => 'CHIP '.$lockedAttempt->provider_purchase_id,
                'notes' => 'Verified CHIP Collect payment.',
                'paid_at' => $paidAt,
            ]);
            $document->paid_amount = round((float) $document->paid_amount + (float) $lockedAttempt->amount, 2);
            $document->status = $document->paid_amount >= $document->total ? 'paid' : 'partial';
            $document->save();

            $lockedAttempt->update([
                'status' => 'paid',
                'paid_at' => $paidAt,
            ]);
        });
    }

    private function authorizeCustomerDocument(Request $request, BillingDocument $document): void
    {
        if ($request->user()->hasRole('admin')) {
            return;
        }

        abort_unless($request->user()->hasRole('customer'), 403);
        $email = strtolower(trim($request->user()->email));
        $userId = $request->user()->id;
        abort_unless(
            in_array($document->type, ['invoice', 'quotation'], true)
            && (
                $document->project()
                    ->where(function ($projects) use ($email, $userId): void {
                        $projects->where('customer_user_id', $userId)
                            ->orWhereHas('customers', fn ($customers) => $customers
                                ->whereRaw('LOWER(TRIM(email)) = ?', [$email]));
                    })
                    ->exists()
                || $document->repairTicket()
                    ->whereHas('project')
                    ->where(function ($ticket) use ($email, $userId): void {
                        $ticket->whereHas('project', fn ($projects) => $projects
                            ->where('customer_user_id', $userId)
                            ->orWhereHas('customers', fn ($customers) => $customers
                                ->whereRaw('LOWER(TRIM(email)) = ?', [$email]))
                            ->orWhereHas('customerAccount', fn ($accounts) => $accounts
                                ->whereRaw('LOWER(TRIM(email)) = ?', [$email]))
                        )
                            ->orWhereHas('customer', fn ($customers) => $customers
                                ->whereRaw('LOWER(TRIM(email)) = ?', [$email]));
                    })
                    ->exists()
            ),
            404,
        );
    }
}
