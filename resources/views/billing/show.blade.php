@extends('layouts.app')

@section('title', $document->document_number)

@section('content')
    @php
        $paidTotal = $document->paid_total;
        $balanceDue = $document->balance_due;
    @endphp
    <div class="container-fluid py-3">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <x-page-header eyebrow="{{ ucfirst($document->type) }}" title="{{ $document->document_number }}" :breadcrumbs="[['label' => 'Billing documents', 'url' => route('billing.index', ['type' => $document->type])], ['label' => $document->document_number]]" :back-url="route('billing.index', ['type' => $document->type])" back-label="Back to billing">
            <x-slot:actions>
                <span class="badge badge-subtle-{{ $document->status === 'paid' ? 'success' : ($document->status === 'partial' ? 'warning' : 'primary') }}">{{ ucfirst($document->status) }}</span>
                <a href="{{ route('billing.documents.pdf', $document) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary"><span class="fas fa-file-pdf me-2"></span>Open PDF</a>
            </x-slot:actions>
        </x-page-header>

        <div class="row g-4">
            <div class="col-xl-8">
                <section class="card mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Customer</p><h2 class="h5 mb-1">{{ $document->customer_name }}</h2>@if($document->customer_email)<p class="mb-0">{{ $document->customer_email }}</p>@endif @if($document->customer_phone)<p class="mb-0">{{ $document->customer_phone }}</p>@endif</div>
                            <div class="col-md-6 text-md-end"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Document dates</p><p class="mb-1">Issued: {{ $document->issued_at->format('M j, Y') }}</p><p class="mb-0">Due: {{ $document->due_date?->format('M j, Y') ?? '—' }}</p></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                    @foreach ($document->items as $item)
                                        <tr><td>{{ $item->description }}@if($item->part)<div class="text-600 fs-10">Stock: {{ $item->part->sku }}</div>@endif</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">RM{{ number_format((float) $item->unit_price, 2) }}</td><td class="text-end">RM{{ number_format((float) $item->line_total, 2) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row justify-content-end"><div class="col-sm-6 col-lg-5">
                            <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>RM{{ number_format((float) $document->subtotal, 2) }}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>Tax ({{ number_format((float) $document->tax_rate, 2) }}%)</span><span>RM{{ number_format((float) $document->tax_amount, 2) }}</span></div>
                            <div class="d-flex justify-content-between border-top pt-2"><strong>Total</strong><strong>RM{{ number_format((float) $document->total, 2) }}</strong></div>
                        </div></div>
                        @if($document->notes)<div class="border-top mt-4 pt-3"><h3 class="h6">Notes / terms</h3><p class="text-700 mb-0">{{ $document->notes }}</p></div>@endif
                    </div>
                </section>

                @if ($document->type === 'invoice')
                    <section class="card">
                        <div class="card-header"><h2 class="h5 mb-0">Payment history</h2></div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0" data-datatables='{"pageLength":10,"order":[[0,"desc"]],"language":{"emptyTable":"No payments recorded."}}'><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>Recorded by</th><th class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    @foreach($document->payments as $payment)
                                        <tr><td>{{ $payment->received_at->format('M j, Y') }}</td><td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td><td>{{ $payment->reference ?? '—' }}</td><td>{{ $payment->user->name }}</td><td class="text-end">RM{{ number_format((float) $payment->amount, 2) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>

            <div class="col-xl-4">
                <section class="card mb-4">
                    <div class="card-body">
                        <p class="text-600 text-uppercase fs-10 fw-bold mb-2">Payment summary</p>
                        <div class="d-flex justify-content-between mb-2"><span>Total</span><strong>RM{{ number_format((float) $document->total, 2) }}</strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Paid</span><strong class="text-success">RM{{ number_format($paidTotal, 2) }}</strong></div>
                        <div class="d-flex justify-content-between border-top pt-2"><span>Balance due</span><strong class="{{ $balanceDue > 0 ? 'text-danger' : 'text-success' }}">RM{{ number_format($balanceDue, 2) }}</strong></div>
                        @if ($document->stock_committed_at)
                            <p class="text-600 fs-10 mt-3 mb-0"><span class="fas fa-box me-1"></span>Linked inventory was deducted on {{ $document->stock_committed_at->format('M j, Y') }}.</p>
                        @endif
                    </div>
                </section>

                @if ($document->type === 'quote' && $document->status !== 'void')
                    <section class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5">Convert to invoice</h2>
                            <p class="text-700 fs-10">Converting issues an invoice, reserves linked parts by deducting available stock, and starts payment tracking.</p>
                            <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#convertQuoteModal">Convert quote</button>
                        </div>
                    </section>
                @endif

                @if ($document->type === 'invoice' && in_array($document->status, ['issued', 'partial'], true) && $balanceDue > 0)
                    <section class="card"><div class="card-body"><h2 class="h5">Record payment</h2><p class="text-700">Record a customer payment against the remaining balance.</p><button class="btn btn-success w-100" type="button" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">Record payment</button></div></section>
                @endif
            </div>
        </div>
    </div>
    @if ($document->type === 'quote' && $document->status !== 'void')
        <div class="modal fade" id="convertQuoteModal" tabindex="-1" aria-labelledby="convertQuoteModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('billing.documents.convert', $document) }}">
                @csrf
                <div class="modal-header"><h2 class="modal-title fs-5" id="convertQuoteModalLabel">Convert quote to invoice?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">This will issue an invoice and deduct linked parts from available inventory.</div>
                <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Convert to invoice</button></div>
            </form>
        </div></div></div>
    @endif
    @if ($document->type === 'invoice' && in_array($document->status, ['issued', 'partial'], true) && $balanceDue > 0)
        <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('billing.documents.payments.store', $document) }}">
                @csrf
                <input type="hidden" name="ui_modal" value="recordPaymentModal">
                <div class="modal-header"><h2 class="modal-title fs-5" id="recordPaymentModalLabel">Record payment</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                                <label class="form-label" for="payment-amount">Amount (RM)</label>
                                <input class="form-control mb-3" id="payment-amount" type="number" name="amount" min="0.01" max="{{ number_format($balanceDue, 2, '.', '') }}" step="0.01" value="{{ number_format($balanceDue, 2, '.', '') }}" required>
                                <label class="form-label" for="payment-method">Method</label>
                                <select class="form-select mb-3" id="payment-method" name="method" required>
                                    <option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="card">Card</option><option value="online">Online</option><option value="other">Other</option>
                                </select>
                                <label class="form-label" for="payment-date">Received on</label>
                                <input class="form-control mb-3" id="payment-date" type="date" name="received_at" value="{{ now()->toDateString() }}" required>
                                <label class="form-label" for="payment-reference">Reference <span class="text-600">(optional)</span></label>
                                <input class="form-control mb-3" id="payment-reference" name="reference" maxlength="120">
                                <label class="form-label" for="payment-note">Note <span class="text-600">(optional)</span></label>
                                <input class="form-control mb-3" id="payment-note" name="note" maxlength="500">
                </div>
                <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Save payment</button></div>
            </form>
        </div></div></div>
    @endif
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>
    @endpush
@endsection
