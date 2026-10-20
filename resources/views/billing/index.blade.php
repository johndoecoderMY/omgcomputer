@extends('layouts.app')

@section('title', 'Billing & Payments')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Finance" title="Billing & payments">
        @role('admin')
            <x-slot name="actions">
                <a class="btn btn-outline-primary" href="{{ route('billing.create', ['type' => 'quotation']) }}"><span class="fas fa-file-alt me-2"></span>New quotation</a>
                <a class="btn btn-primary" href="{{ route('billing.create') }}"><span class="fas fa-plus me-2"></span>New invoice</a>
            </x-slot>
        @endrole
    </x-page-header>

    <div class="alert alert-info"><strong>Quotation:</strong> proposed work and estimated price; it does not deduct inventory. <strong>Invoice:</strong> issued sale; linked stock is deducted and payments can be recorded.</div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form class="row g-2 mb-3" method="GET" action="{{ route('billing.index') }}">
        <div class="col-md-4"><label class="visually-hidden" for="type">Document type</label><select class="form-select" id="type" name="type"><option value="">All document types</option><option value="invoice" @selected(request('type') === 'invoice')>Invoices</option><option value="quotation" @selected(request('type') === 'quotation')>Quotations</option></select></div>
        <div class="col-md-4"><label class="visually-hidden" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach (['draft', 'issued', 'partial', 'part_paid', 'paid', 'void'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('billing.index') }}">Reset</a></div>
    </form>

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
</div>
@endsection