@extends('layouts.app')

@section('title', 'Customer Bookings')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="Customer bookings"><x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('bookings.create') }}" target="_blank"><span class="fas fa-calendar-plus me-2"></span>Public booking page</a></x-slot></x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="row g-2 mb-3" method="GET" action="{{ route('bookings.index') }}"><div class="col-md-4"><label class="visually-hidden" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Reset</a></div></form>
    <div class="card p-2"><div class="table-responsive">
        <table class="table align-middle mb-0" data-datatable
            data-source="{{ route('bookings.datatable', request()->only('status')) }}"
            data-columns="{{ json_encode([
                ['data' => 'starts_at', 'name' => 'starts_at'],
                ['data' => 'reference', 'name' => 'reference'],
                ['data' => 'customer_contact', 'orderable' => false, 'searchable' => false],
                ['data' => 'service', 'orderable' => false, 'searchable' => false],
                ['data' => 'request_notes', 'name' => 'request_notes'],
                ['data' => 'status', 'name' => 'status'],
                ['data' => 'management', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>When</th><th>Reference</th><th>Customer</th><th>Service</th><th>Request</th><th>Status</th><th>Status / assignee</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection