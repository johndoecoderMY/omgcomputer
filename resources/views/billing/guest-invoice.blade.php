@extends('layouts.guest')

@section('title', __('Pay invoice :number', ['number' => $document->number]))
@section('meta_robots', 'noindex,nofollow')
@section('meta_description', __('Securely review and pay your OMG Computer invoice.'))
@section('canonical_url', url('/'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h1 class="h4 mb-1">{{ __('Invoice :number', ['number' => $document->number]) }}</h1>
                    <div class="text-body-secondary">{{ __('Secure invoice payment') }}</div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="small text-body-secondary">{{ __('Issued') }}</div>
                            <div>{{ $document->issue_date->format('M j, Y') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-body-secondary">{{ __('Due') }}</div>
                            <div>{{ $document->due_date?->format('M j, Y') ?? __('Not set') }}</div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr><th>{{ __('Description') }}</th><th class="text-end">{{ __('Qty') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('billing.pdf.item_discount') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($document->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">RM {{ number_format($item->line_total, 2) }}</td>
                                        <td class="text-end">{{ (float) $item->discount_amount > 0 ? '- RM '.number_format($item->discount_amount, 2) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><td colspan="3" class="text-end">{{ __('Subtotal') }}</td><td class="text-end">RM {{ number_format($document->subtotal, 2) }}</td></tr>
                                @if ($document->items->sum('discount_amount') > 0)
                                    <tr><td colspan="3" class="text-end">{{ __('billing.pdf.item_discount') }}</td><td class="text-end">- RM {{ number_format($document->items->sum('discount_amount'), 2) }}</td></tr>
                                @endif
                                @if ((float) $document->discount_amount > 0)
                                    <tr><td colspan="3" class="text-end">{{ __('Discount') }}</td><td class="text-end">- RM {{ number_format($document->discount_amount, 2) }}</td></tr>
                                @endif
                                @if ((float) $document->tax_amount > 0)
                                    <tr><td colspan="3" class="text-end">{{ __('Tax') }} ({{ number_format($document->tax_rate, 2) }}%)</td><td class="text-end">RM {{ number_format($document->tax_amount, 2) }}</td></tr>
                                @endif
                                <tr><th colspan="3" class="text-end">{{ __('Invoice total') }}</th><th class="text-end">RM {{ number_format($document->total, 2) }}</th></tr>
                                <tr><td colspan="3" class="text-end">{{ __('Paid') }}</td><td class="text-end">RM {{ number_format($document->paid_amount, 2) }}</td></tr>
                                <tr class="fw-bold"><td colspan="3" class="text-end">{{ __('Balance due') }}</td><td class="text-end">RM {{ number_format($document->balance, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($chipEnabled && $hasValidCustomerEmail)
                        <form method="POST" action="{{ route('billing.guest.chip.checkout', [$document, request()->route('token')]) }}">
                            @csrf
                            <p class="mb-2">{{ __('Outstanding balance: RM :balance.', ['balance' => number_format($document->balance, 2)]) }}</p>
                            <label class="form-label" for="guest-chip-payment-amount">{{ __('Payment amount (RM)') }}</label>
                            <input class="form-control mb-3" id="guest-chip-payment-amount" name="amount" type="number" min="0.01"
                                max="{{ number_format($document->balance, 2, '.', '') }}" step="0.01"
                                value="{{ number_format($document->balance, 2, '.', '') }}" required>
                            <p class="small text-body-secondary">{{ __("Choose any amount up to the balance. Available payment methods depend on the channels enabled for the merchant's CHIP account.") }}</p>
                            <button class="btn btn-primary w-100" type="submit">
                                <span class="bi bi-shield-check me-2" aria-hidden="true"></span>{{ __('Continue to secure payment') }}
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">
                            @if (! $chipEnabled)
                                {{ __('Online payment is temporarily unavailable. Please contact OMG Computer to arrange payment.') }}
                            @else
                                {{ __('Online payment is unavailable for this invoice. Please contact OMG Computer.') }}
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-footer small text-body-secondary">
                    {{ __('This private payment link expires :date. Do not share it with anyone other than the intended payer.', ['date' => $document->guest_payment_link_expires_at->format('M j, Y g:i A')]) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
