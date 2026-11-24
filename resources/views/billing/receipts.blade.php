@extends('layouts.app')

@section('title', 'Payment receipts')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Billing & payments" title="Payment receipts"
        description="Receipts are available for payments recorded against invoices. Open a receipt to print or save its PDF." :back-url="route('billing.index')" back-label="Back to billing" />

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header fw-semibold">Payment receipts</div>
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
                            <td>{{ __('billing.pdf.payment_method_'.$payment->method) }}</td>
                            <td class="text-end">RM {{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('billing.receipts.pdf', $payment) }}" target="_blank" rel="noopener">
                                    <span class="bi bi-filetype-pdf me-1" aria-hidden="true"></span>View / print
                                </a>
                                @unless (auth()->user()->hasRole('customer'))
                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                        data-bs-toggle="modal" data-bs-target="#send-receipt-modal-{{ $payment->id }}">
                                        <span class="bi bi-envelope me-1" aria-hidden="true"></span>
                                        {{ $payment->receipt_emailed_at ? 'Resend email' : 'Send email' }}
                                    </button>
                                @endunless
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
    @unless (auth()->user()->hasRole('customer'))
        @foreach ($payments as $payment)
            @include('billing._send-modal', ['sendModal' => [
                'id' => 'send-receipt-modal-'.$payment->id,
                'title' => $payment->receipt_emailed_at ? 'Resend receipt' : 'Send receipt',
                'description' => 'Email the receipt PDF or share its private WhatsApp PDF link. The link works for guests without an account and expires after 7 days.',
                'action' => route('billing.receipts.send', $payment),
                'email' => $payment->document->customer_email,
                'email_preview_url' => route('billing.receipts.email-preview', $payment),
                'pdf_preview_url' => route('billing.receipts.pdf', $payment),
                'phone' => $payment->document->customer_phone,
                'whatsapp_key' => 'billing.whatsapp.receipt',
                'whatsapp_replacements' => [
                    'name' => $payment->document->customer_name,
                    'amount' => number_format((float) $payment->amount, 2),
                    'invoice' => $payment->document->number,
                ],
                'whatsapp_urls' => $guestReceiptUrls[$payment->id] ?? [],
                'filename' => $payment->receipt_number,
                'submit_label' => 'Send receipt',
                'mark_sent_url' => null,
            ]])
        @endforeach
    @endunless
</div>
@endsection
