@extends('layouts.guest')

@section('title', 'Pay invoice '.$document->number)
@section('meta_robots', 'noindex,nofollow')
@section('meta_description', 'Securely review and pay your OMG Computer invoice.')
@section('canonical_url', url('/'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h1 class="h4 mb-1">Invoice {{ $document->number }}</h1>
                    <div class="text-body-secondary">Secure invoice payment</div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="small text-body-secondary">Issued</div>
                            <div>{{ $document->issue_date->format('M j, Y') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-body-secondary">Due</div>
                            <div>{{ $document->due_date?->format('M j, Y') ?? 'Not set' }}</div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Amount</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($document->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">RM {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><th colspan="2" class="text-end">Invoice total</th><th class="text-end">RM {{ number_format($document->total, 2) }}</th></tr>
                                <tr><td colspan="2" class="text-end">Paid</td><td class="text-end">RM {{ number_format($document->paid_amount, 2) }}</td></tr>
                                <tr class="fw-bold"><td colspan="2" class="text-end">Balance due</td><td class="text-end">RM {{ number_format($document->balance, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($chipEnabled && $hasValidCustomerEmail)
                        <form method="POST" action="{{ route('billing.guest.chip.checkout', [$document, request()->route('token')]) }}">
                            @csrf
                            <p class="mb-2">Continue to CHIP to pay the outstanding balance. Available payment methods, including QR or DuitNow, depend on the merchant's CHIP settings.</p>
                            <button class="btn btn-primary w-100" type="submit">
                                <span class="bi bi-shield-check me-2" aria-hidden="true"></span>Continue to secure payment
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">
                            @if (! $chipEnabled)
                                Online payment is temporarily unavailable. Please contact OMG Computer to arrange payment.
                            @else
                                Online payment is unavailable for this invoice. Please contact OMG Computer.
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-footer small text-body-secondary">
                    This private payment link expires {{ $document->guest_payment_link_expires_at->format('M j, Y g:i A') }}. Do not share it with anyone other than the intended payer.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
