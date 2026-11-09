@extends('layouts.app')

@section('title', 'Billing & Payments')

@section('content')
<style>
    .billing-filter-fab {
        display: none;
    }

    @media (max-width: 991.98px) {
        .billing-filter-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1030;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 .5rem 1.25rem rgba(23, 32, 51, .28);
        }
    }
</style>
<div class="container-fluid py-3">
    <x-page-header eyebrow="Finance" title="Billing & payments">
        @role('admin')
            <x-slot name="actions">
                <a class="btn btn-outline-primary" href="{{ route('billing.create', ['type' => 'quotation']) }}"><span class="bi bi-file-text me-2"></span>New quotation</a>
                <a class="btn btn-primary" href="{{ route('billing.create') }}"><span class="bi bi-plus me-2"></span>New invoice</a>
            </x-slot>
        @endrole
    </x-page-header>

    <div class="alert alert-info"><strong>Quotation:</strong> proposed work and estimated price; it does not deduct inventory. <strong>Invoice:</strong> issued sale; linked stock is deducted and payments can be recorded.</div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @php($activeBillingFilters = (int) request()->filled('type') + (int) request()->filled('status'))
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-outline-primary d-none d-lg-inline-flex" type="button" data-bs-toggle="modal" data-bs-target="#billing-filters-modal">
            <span class="bi bi-filter me-2" aria-hidden="true"></span>Filters
            @if ($activeBillingFilters > 0)
                <span class="badge text-bg-primary ms-1">{{ $activeBillingFilters }}</span>
            @endif
        </button>
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
                data-source="{{ route('billing.datatable', request()->only(['type', 'status'])) }}"
                data-columns="{{ json_encode([
                    ['data' => 'document', 'name' => 'number'],
                    ['data' => 'customer_name', 'name' => 'customer_name'],
                    ['data' => 'issue_date', 'name' => 'issue_date'],
                    ['data' => 'status_label', 'name' => 'status'],
                    ['data' => 'total', 'name' => 'total'],
                    ['data' => 'paid_amount', 'name' => 'paid_amount'],
                    ['data' => 'balance', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead><tr><th>Document</th><th>Customer</th><th>Issued</th><th>Status</th><th>Total</th><th>Paid</th><th>Balance</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <button class="billing-filter-fab btn btn-primary rounded-pill px-4 py-3" type="button" data-bs-toggle="modal" data-bs-target="#billing-filters-modal">
        <span class="bi bi-filter me-2" aria-hidden="true"></span>Filters
        @if ($activeBillingFilters > 0)
            <span class="badge text-bg-light text-primary ms-1">{{ $activeBillingFilters }}</span>
        @endif
    </button>
    <div class="modal fade" id="billing-filters-modal" tabindex="-1" aria-labelledby="billing-filters-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('billing.index') }}">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="billing-filters-title">Filter billing documents</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="billing-filter-type">Document type</label>
                        <select class="form-select" id="billing-filter-type" name="type">
                            <option value="">All document types</option>
                            <option value="invoice" @selected(request('type') === 'invoice')>Invoices</option>
                            <option value="quotation" @selected(request('type') === 'quotation')>Quotations</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="billing-filter-status">Status</label>
                        <select class="form-select" id="billing-filter-status" name="status">
                            <option value="">All statuses</option>
                            @foreach (['draft', 'sent', 'accepted', 'rejected', 'issued', 'partial', 'part_paid', 'paid', 'void'] as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('billing.index') }}">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection