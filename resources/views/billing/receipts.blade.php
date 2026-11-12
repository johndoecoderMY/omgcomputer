@extends('layouts.app')

@section('title', 'Payment receipts')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Billing & payments" title="Payment receipts"
        description="Receipts are available for payments recorded against invoices. Open a receipt to print or save its PDF." :back-url="route('billing.index')" back-label="Back to billing" />

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        @unless (auth()->user()->hasRole('customer'))<th>Customer</th>@endunless
                        <th>Invoice</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="fw-semibold">{{ $payment->receipt_number }}</td>
                            <td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td>
                            @unless (auth()->user()->hasRole('customer'))<td>{{ $payment->document->customer_name }}</td>@endunless
                            <td><a href="{{ route('billing.documents.show', $payment->document) }}">{{ $payment->document->number }}</a></td>
                            <td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td>
                            <td class="text-end">RM {{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('billing.receipts.pdf', $payment) }}" target="_blank" rel="noopener">
                                    <span class="bi bi-filetype-pdf me-1" aria-hidden="true"></span>View / print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-body-secondary py-4" colspan="{{ auth()->user()->hasRole('customer') ? 6 : 7 }}">No payment receipts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
