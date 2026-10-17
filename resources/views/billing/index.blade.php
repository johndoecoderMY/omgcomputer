@extends('layouts.app')

@section('title', 'Billing & Payments')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Finance" title="Billing & payments">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('billing.create', ['type' => 'quotation']) }}"><span class="fas fa-file-alt me-2"></span>New quotation</a>
            <a class="btn btn-primary" href="{{ route('billing.create') }}"><span class="fas fa-plus me-2"></span>New invoice</a>
        </x-slot>
    </x-page-header>

    <div class="alert alert-info"><strong>Quotation:</strong> proposed work and estimated price; it does not deduct inventory. <strong>Invoice:</strong> issued sale; linked stock is deducted and payments can be recorded.</div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Number or customer"></div>
        <div class="col-md-3"><select class="form-select" name="type"><option value="">All document types</option><option value="invoice" @selected(request('type') === 'invoice')>Invoices</option><option value="quotation" @selected(request('type') === 'quotation')>Quotations</option></select></div>
        <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>@foreach (['draft', 'issued', 'partial', 'paid', 'void'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary flex-grow-1">Filter</button><a class="btn btn-outline-secondary" href="{{ route('billing.index') }}">Reset</a></div>
    </form>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Document</th><th>Customer</th><th>Issued</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('billing.documents.show', $document) }}">{{ $document->number }}</a><div class="small text-body-secondary">{{ ucfirst($document->type) }}</div></td>
                            <td>{{ $document->customer_name }}</td><td>{{ $document->issue_date->format('M j, Y') }}</td>
                            <td><span class="badge text-bg-{{ $document->status === 'paid' ? 'success' : ($document->status === 'void' ? 'secondary' : ($document->status === 'draft' ? 'light' : 'warning')) }}">{{ str_replace('_', ' ', ucfirst($document->status)) }}</span></td>
                            <td class="text-end">RM {{ number_format($document->total, 2) }}</td>
                            <td class="text-end">RM {{ number_format($document->paid_amount, 2) }}</td>
                            <td class="text-end">RM {{ number_format($document->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-5">No billing documents yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $documents->links() }}</div>
    </div>
</div>
@endsection