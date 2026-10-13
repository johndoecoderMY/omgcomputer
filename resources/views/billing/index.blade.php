@extends('layouts.app')

@section('title', 'Billing & Payments')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header
            eyebrow="Billing & payments"
            title="{{ $type === 'quote' ? 'Quotations' : ($type === 'invoice' ? 'Invoices' : 'Documents') }}"
            description="Create customer estimates and invoices, record payments, and download PDFs."
        >
            <x-slot:actions>
                <a href="{{ route('billing.create', ['type' => 'quote']) }}" class="btn btn-outline-primary"><span class="fas fa-file-lines me-2"></span>New quote</a>
                <a href="{{ route('billing.create', ['type' => 'invoice']) }}" class="btn btn-primary"><span class="fas fa-file-invoice-dollar me-2"></span>New invoice</a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h5 mb-0">Documents</h2>
                <button class="btn btn-sm btn-falcon-default" type="button" data-bs-toggle="modal" data-bs-target="#billingFilterModal"><span class="fas fa-filter me-1"></span>Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" data-datatables='{"pageLength":10,"order":[[2,"desc"]],"language":{"emptyTable":"No billing documents yet. Create a quote or invoice to get started."}}'>
                    <thead><tr><th>Document</th><th>Customer</th><th>Issued</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                        @foreach ($documents as $document)
                            @php
                                $paid = (float) $document->payments_sum_amount;
                                $balance = max(0, (float) $document->total - $paid);
                            @endphp
                            <tr>
                                <td><a class="fw-bold" href="{{ route('billing.documents.show', $document) }}">{{ $document->document_number }}</a><div class="text-600 fs-10">{{ ucfirst($document->type) }} · {{ $document->items_count }} line(s)</div></td>
                                <td>{{ $document->customer_name }}</td>
                                <td>{{ $document->issued_at->format('M j, Y') }}</td>
                                <td><span class="badge badge-subtle-{{ $document->status === 'paid' ? 'success' : ($document->status === 'partial' ? 'warning' : 'primary') }}">{{ ucfirst($document->status) }}</span></td>
                                <td class="text-end">RM{{ number_format((float) $document->total, 2) }}</td>
                                <td class="text-end">RM{{ number_format($paid, 2) }}</td>
                                <td class="text-end">RM{{ number_format($balance, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="billingFilterModal" tabindex="-1" aria-labelledby="billingFilterModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="GET" action="{{ route('billing.index') }}">
            <div class="modal-header"><h2 class="modal-title fs-5" id="billingFilterModalLabel">Filter billing documents</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="billing-type">Document type</label><select class="form-select" id="billing-type" name="type"><option value="">All documents</option><option value="quote" @selected($type === 'quote')>Quotes</option><option value="invoice" @selected($type === 'invoice')>Invoices</option></select></div>
                <div><label class="form-label" for="billing-status">Status</label><select class="form-select" id="billing-status" name="status"><option value="">All statuses</option>@foreach (['draft', 'issued', 'partial', 'paid', 'void'] as $value)<option value="{{ $value }}" @selected($status === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
            </div>
            <div class="modal-footer"><a class="btn btn-falcon-default me-auto" href="{{ route('billing.index') }}">Clear filters</a><button class="btn btn-primary" type="submit">Apply filters</button></div>
        </form>
    </div></div></div>
@endsection
