@extends('layouts.app')

@section('title', $document->number)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="{{ ucfirst($document->type) }}" :title="$document->number" :back-url="route('billing.index')" back-label="Back to billing">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('billing.documents.pdf', $document) }}" target="_blank"><span class="fas fa-file-pdf me-2"></span>PDF</a>
            @if ($document->status === 'draft')<form method="POST" action="{{ route('billing.documents.convert', $document) }}">@csrf<button class="btn btn-primary" onclick="return confirm('Issue this document and deduct listed stock?')">{{ $document->type === 'quotation' ? 'Convert to invoice' : 'Issue invoice' }}</button></form>@endif
            @if (! in_array($document->status, ['void', 'paid'], true) && (float) $document->paid_amount === 0.0)<form method="POST" action="{{ route('billing.documents.void', $document) }}">@csrf<button class="btn btn-outline-danger" onclick="return confirm('Void this document?')">Void</button></form>@endif
        </x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8"><div class="card h-100"><div class="card-header fw-semibold">Document details</div><div class="card-body">
            <div class="row g-3"><div class="col-md-6"><div class="small text-body-secondary">Bill to</div><strong>{{ $document->customer_name }}</strong><div>{{ $document->customer_email }}</div><div>{{ $document->customer_phone }}</div><div class="text-pre-line">{{ $document->customer_address }}</div></div><div class="col-md-3"><div class="small text-body-secondary">Issue date</div><div>{{ $document->issue_date->format('M j, Y') }}</div><div class="small text-body-secondary mt-2">Due date</div><div>{{ $document->due_date?->format('M j, Y') ?? 'Not set' }}</div></div><div class="col-md-3"><div class="small text-body-secondary">Status</div><span class="badge text-bg-primary">{{ str_replace('_', ' ', ucfirst($document->status)) }}</span></div></div>
            <hr><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th></tr></thead><tbody>@foreach ($document->items as $item)<tr><td>{{ $item->description }}</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">${{ number_format($item->unit_price, 2) }}</td><td class="text-end">${{ number_format($item->line_total, 2) }}</td></tr>@endforeach</tbody></table></div>
            @if ($document->notes)<div class="small text-body-secondary">{{ $document->notes }}</div>@endif
        </div></div></div>
        <div class="col-lg-4"><div class="card mb-3"><div class="card-body"><div class="d-flex justify-content-between"><span>Subtotal</span><span>${{ number_format($document->subtotal, 2) }}</span></div><div class="d-flex justify-content-between"><span>Tax ({{ number_format($document->tax_rate, 2) }}%)</span><span>${{ number_format($document->tax_amount, 2) }}</span></div><hr><div class="d-flex justify-content-between fw-bold"><span>Total</span><span>${{ number_format($document->total, 2) }}</span></div><div class="d-flex justify-content-between mt-2"><span>Paid</span><span>${{ number_format($document->paid_amount, 2) }}</span></div><div class="d-flex justify-content-between fw-semibold"><span>Balance</span><span>${{ number_format($document->balance, 2) }}</span></div></div></div>
            @if ($document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid'], true))<div class="card"><div class="card-header fw-semibold">Record payment</div><form method="POST" action="{{ route('billing.documents.payments.store', $document) }}">@csrf<div class="card-body"><label class="form-label" for="amount">Amount</label><input class="form-control mb-3" id="amount" name="amount" type="number" min="0.01" max="{{ $document->balance }}" step="0.01" value="{{ number_format($document->balance, 2, '.', '') }}" required><label class="form-label" for="method">Method</label><select class="form-select mb-3" id="method" name="method" required>@foreach (['cash', 'card', 'bank_transfer', 'online', 'other'] as $method)<option value="{{ $method }}">{{ str_replace('_', ' ', ucfirst($method)) }}</option>@endforeach</select><label class="form-label" for="reference">Reference</label><input class="form-control" id="reference" name="reference" maxlength="255"></div><div class="card-footer"><button class="btn btn-primary w-100">Record payment</button></div></form></div>@endif
        </div>
    </div>
    <div class="card"><div class="card-header fw-semibold">Payment history</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead><tbody>@forelse ($document->payments as $payment)<tr><td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td><td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td><td>{{ $payment->reference ?: '—' }}</td><td class="text-end">${{ number_format($payment->amount, 2) }}</td></tr>@empty<tr><td class="text-center text-body-secondary py-4" colspan="4">No payments recorded.</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection