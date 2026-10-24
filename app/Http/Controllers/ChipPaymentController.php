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
        abort_unless(
            config('services.chip.enabled')
            && $document->type === 'invoice'
            && in_array($document->status, ['issued', 'partial', 'part_paid'], true)
            && $document->balance > 0,
            422,
            'This invoice is not eligible for CHIP checkout.',
        );
        $clientEmail = filter_var($document->customer_email, FILTER_VALIDATE_EMAIL)
            ? $document->customer_email
            : $request->user()->email;

        $attempt = BillingPaymentAttempt::query()->create([
            'uuid' => (string) Str::uuid(),
            'billing_document_id' => $document->id,
            'user_id' => $request->user()->id,
            'amount' => $document->balance,
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
                'brand_id' => config('services.chip.brand_id'),
                'reference' => $document->number.'-'.$attempt->uuid,
                'success_redirect' => route('billing.chip.return', $attempt->uuid),
                'failure_redirect' => route('billing.chip.return', $attempt->uuid),
                'success_callback' => route('webhooks.chip.collect', $attempt->uuid),
            ]);
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

    public function callback(BillingPaymentAttempt $attempt, Request $request, ChipCollect $chip): Response
    {
        abort_unless($chip->verifyCallback($request->getContent(), $request->header('X-Signature')), 401);

        $payload = $request->json()->all();
        $purchaseId = $payload['id'] ?? null;
        abort_unless(
            is_string($purchaseId)
            && $attempt->provider_purchase_id !== null
            && hash_equals($attempt->provider_purchase_id, $purchaseId),
            404,
        );

        $purchase = $chip->purchase($attempt->provider_purchase_id);
        abort_unless(($purchase['id'] ?? null) === $attempt->provider_purchase_id, 502, 'CHIP returned a different purchase.');
        abort_unless(($purchase['status'] ?? null) === 'paid', 409, 'CHIP has not confirmed this purchase as paid.');
        $this->recordVerifiedPayment($attempt);

        return response('', Response::HTTP_OK);
    }

    public function return(BillingPaymentAttempt $attempt, Request $request, ChipCollect $chip): RedirectResponse
    {
        $attempt->load('document');
        $this->authorizeCustomerDocument($request, $attempt->document);

        if ($attempt->status === 'paid') {
            return redirect()->route('billing.documents.show', $attempt->document)
                ->with('success', 'CHIP confirmed your payment.');
        }

        if ($attempt->provider_purchase_id) {
            $purchase = $chip->purchase($attempt->provider_purchase_id);
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
