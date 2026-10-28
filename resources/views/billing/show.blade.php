@extends('layouts.app')

@section('title', $document->number)

@section('content')
@php
    $whatsappNumber = preg_replace('/\D+/', '', (string) $document->customer_phone);
    if (str_starts_with($whatsappNumber, '0')) {
        $whatsappNumber = '60'.substr($whatsappNumber, 1);
    }
    $quotationWhatsappUrl = $whatsappNumber !== ''
        ? 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode('Hi '.$document->customer_name.', here is your quotation '.$document->number.' from OMG Computer (RM '.number_format((float) $document->total, 2).'). Please view or download it here: '.route('billing.documents.show', $document))
        : '';
@endphp
<div class="container-fluid py-3">
    <x-page-header eyebrow="{{ ucfirst($document->type) }}" :title="$document->number" :back-url="route('billing.index')" back-label="Back to billing">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('billing.documents.pdf', $document) }}" target="_blank"><span class="bi bi-filetype-pdf me-2"></span>PDF</a>
            @if (! auth()->user()->hasRole('customer') && ($document->status === 'draft' || ($document->type === 'quotation' && $document->status === 'rejected')))<a class="btn btn-outline-primary" href="{{ route('billing.documents.edit', $document) }}"><span class="bi bi-pencil me-2"></span>{{ $document->status === 'rejected' ? 'Edit rejected quotation' : 'Edit draft' }}</a>@endif
            @if ($document->repairTicket)<a class="btn btn-outline-secondary" href="{{ route('tickets.show', $document->repairTicket) }}"><span class="bi bi-ticket-perforated me-2"></span>{{ $document->repairTicket->ticket_number }}</a>@endif
            @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && in_array($document->status, ['draft', 'rejected'], true))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#send-quotation-modal">
                    <span class="bi bi-send me-2"></span>{{ $document->status === 'rejected' ? 'Resend quotation' : 'Mark as sent' }}
                </button>
            @elseif (auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'sent')
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}" class="d-inline">@csrf<input type="hidden" name="status" value="accepted"><button class="btn btn-success"><span class="bi bi-check-circle me-2"></span>Accept quotation</button></form>
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}" class="d-inline">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-outline-danger"><span class="bi bi-x-circle me-2"></span>Reject quotation</button></form>
            @elseif (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'sent')
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}">@csrf<input type="hidden" name="status" value="accepted"><button class="btn btn-success">Mark accepted</button></form>
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-outline-danger">Mark rejected</button></form>
            @endif
            @if (! auth()->user()->hasRole('customer') && (($document->type === 'quotation' && $document->status === 'accepted') || ($document->type === 'invoice' && $document->status === 'draft')))<form method="POST" action="{{ route('billing.documents.convert', $document) }}">@csrf<button class="btn btn-primary" onclick="return confirm('Issue this document and deduct listed stock?')">{{ $document->type === 'quotation' ? 'Convert to invoice' : 'Issue invoice' }}</button></form>@endif
            @if (! auth()->user()->hasRole('customer') && ! in_array($document->status, ['void', 'paid'], true) && (float) $document->paid_amount === 0.0)<form method="POST" action="{{ route('billing.documents.void', $document) }}">@csrf<button class="btn btn-outline-danger" onclick="return confirm('Void this document?')">Void</button></form>@endif
        </x-slot>
    </x-page-header>

    @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && in_array($document->status, ['draft', 'rejected'], true))
        <div class="modal fade" id="send-quotation-modal" tabindex="-1" aria-labelledby="send-quotation-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="send-quotation-modal-label">{{ $document->status === 'rejected' ? 'Resend quotation' : 'Send quotation' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-body-secondary mb-3">Send the quotation PDF by email, and automatically add it to the customer panel when the customer has an account.</p>
                        <div class="d-grid gap-3">
                            <form method="POST" action="{{ route('billing.documents.send', $document) }}" class="border rounded p-3">
                                @csrf
                                <label class="form-label fw-semibold" for="quotation-send-email"><span class="bi bi-envelope me-2"></span>Email quotation PDF</label>
                                <div class="small text-body-secondary mb-2">Also appears in the customer panel when this email belongs to a registered account.</div>
                                <input class="form-control mb-3" id="quotation-send-email" type="email" name="email" value="{{ old('email', $document->customer_email) }}" placeholder="Customer email" required>
                                <button class="btn btn-primary w-100" type="submit">Send quotation</button>
                            </form>
                            @if ($quotationWhatsappUrl !== '')
                                <div class="border rounded p-3">
                                    <div class="fw-semibold mb-1"><span class="bi bi-whatsapp me-2"></span>WhatsApp</div>
                                    <div class="small text-body-secondary mb-3">Open a WhatsApp message containing the quotation details and customer-panel link.</div>
                                    <a class="btn btn-success w-100 quotation-whatsapp-link" href="{{ $quotationWhatsappUrl }}" target="_blank" rel="noopener" data-status-url="{{ route('billing.documents.quotation-status', $document) }}" data-csrf="{{ csrf_token() }}">Open WhatsApp draft</a>
                                </div>
                            @else
                                <div class="alert alert-secondary mb-0">Add a customer phone number to create a WhatsApp quotation link.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
    document.querySelectorAll('.quotation-whatsapp-link').forEach((link) => {
        link.addEventListener('click', () => {
            fetch(link.dataset.statusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': link.dataset.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ status: 'sent', delivery: 'whatsapp' }),
            });
        });
    });
    </script>
    @endpush

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8"><div class="card h-100"><div class="card-header fw-semibold">Document details</div><div class="card-body">
            <div class="row g-3"><div class="col-md-6"><div class="small text-body-secondary">Bill to</div>@if ($document->type === 'quotation' && $customerRecord && ! auth()->user()->hasRole('customer'))<a class="fw-semibold" href="{{ route('customers.show', $customerRecord) }}">{{ $document->customer_name }}</a>@else<strong>{{ $document->customer_name }}</strong>@endif<div>{{ $document->customer_email }}</div><div>{{ $document->customer_phone }}</div><div class="text-pre-line">{{ $document->customer_address }}</div></div><div class="col-md-3"><div class="small text-body-secondary">Issue date</div><div>{{ $document->issue_date->format('M j, Y') }}</div><div class="small text-body-secondary mt-2">Due date</div><div>{{ $document->due_date?->format('M j, Y') ?? 'Not set' }}</div></div><div class="col-md-3"><div class="small text-body-secondary">Status</div><x-status-badge :status="$document->status" :label="str_replace('_', ' ', ucfirst($document->status))" /></div></div>
            <hr><div class="table-responsive"><table class="table align-middle table-bordered"><thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th></tr></thead><tbody>@foreach ($document->items as $item)<tr><td>{{ $item->description }}</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">RM {{ number_format($item->unit_price, 2) }}</td><td class="text-end">RM {{ number_format($item->line_total, 2) }}</td></tr>@endforeach</tbody></table></div>
            @if ($document->notes)<div class="small text-body-secondary">{{ $document->notes }}</div>@endif
        </div></div></div>
        <div class="col-lg-4"><div class="card mb-3 p-2"><div class="card-body"><div class="d-flex justify-content-between"><span>Subtotal</span><span>RM {{ number_format($document->subtotal, 2) }}</span></div>@if ((float) $document->tax_amount > 0)<div class="d-flex justify-content-between"><span>Tax ({{ number_format($document->tax_rate, 2) }}%)</span><span>RM {{ number_format($document->tax_amount, 2) }}</span></div>@endif<hr><div class="d-flex justify-content-between fw-bold"><span>Total</span><span>RM {{ number_format($document->total, 2) }}</span></div><div class="d-flex justify-content-between mt-2"><span>Paid</span><span>RM {{ number_format($document->paid_amount, 2) }}</span></div><div class="d-flex justify-content-between fw-semibold"><span>Balance</span><span>RM {{ number_format($document->balance, 2) }}</span></div></div></div>
            @if (auth()->user()->hasRole('customer') && config('services.chip.enabled') && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid'], true) && $document->balance > 0)<div class="card mb-3"><div class="card-header fw-semibold">Pay securely with CHIP</div><form method="POST" action="{{ route('billing.chip.checkout', $document) }}">@csrf<div class="card-body"><p>Pay the outstanding balance of <strong>RM {{ number_format($document->balance, 2) }}</strong>.</p><div class="d-flex flex-wrap gap-2 mb-3" aria-label="Available payment options"><span class="badge text-bg-light border"><span class="bi bi-qr-code-scan me-1" aria-hidden="true"></span>QR pay</span><span class="badge text-bg-light border">DuitNow</span><span class="badge text-bg-light border">Online banking</span><span class="badge text-bg-light border">Card</span></div><small class="text-body-secondary d-block">Available QR, DuitNow and online methods depend on the payment channels enabled for this CHIP merchant account.</small></div><div class="card-footer"><button class="btn btn-primary w-100"><span class="bi bi-shield-check me-2" aria-hidden="true"></span>Continue to CHIP checkout</button></div></form></div>@endif
            @if (auth()->user()->hasRole('admin') && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid'], true))<div class="card"><div class="card-header fw-semibold">Record payment</div><form method="POST" action="{{ route('billing.documents.payments.store', $document) }}">@csrf<div class="card-body"><label class="form-label" for="amount">Amount</label><input class="form-control mb-3" id="amount" name="amount" type="number" min="0.01" max="{{ $document->balance }}" step="0.01" value="{{ number_format($document->balance, 2, '.', '') }}" required><label class="form-label" for="method">Method</label><select class="form-select mb-3" id="method" name="method" required>@foreach (['cash', 'card', 'bank_transfer', 'online', 'other'] as $method)<option value="{{ $method }}">{{ str_replace('_', ' ', ucfirst($method)) }}</option>@endforeach</select><label class="form-label" for="reference">Reference</label><input class="form-control" id="reference" name="reference" maxlength="255"></div><div class="card-footer"><button class="btn btn-primary w-100">Record payment</button></div></form></div>@endif
        </div>
    </div>
    <div class="card"><div class="card-header fw-semibold">Payment history</div><div class="table-responsive"><table class="table mb-0 table-bordered"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead><tbody>@forelse ($document->payments as $payment)<tr><td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td><td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td><td>{{ $payment->reference ?: '—' }}</td><td class="text-end">RM {{ number_format($payment->amount, 2) }}</td></tr>@empty<tr><td class="text-center text-body-secondary py-4" colspan="4">No payments recorded.</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection