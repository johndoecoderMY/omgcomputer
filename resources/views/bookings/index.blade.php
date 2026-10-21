@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="{{ $customerView ? 'My bookings' : 'Bookings' }}">
      
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @php
        $bookingFilters = array_merge(
            request()->only(['status', 'service', 'customer_name', 'starts_from', 'starts_to']),
            is_array(request('filter')) ? request('filter') : [],
        );
        $activeBookingFilters = collect(['status', 'service', 'customer_name', 'starts_from', 'starts_to'])
            ->filter(fn (string $filter): bool => filled($bookingFilters[$filter] ?? null))
            ->count();
    @endphp
    <div class="card p-2">
        <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#booking-filters-modal">
                <span class="fas fa-filter me-2" aria-hidden="true"></span>Filters
                @if ($activeBookingFilters > 0)
                    <span class="badge text-bg-primary ms-1">{{ $activeBookingFilters }}</span>
                @endif
            </button>
        </div>
        <div class="table-responsive">
        <table class="table align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('bookings.datatable', ['filter' => $bookingFilters]) }}"
            data-columns="{{ json_encode([
                ['data' => 'starts_at', 'name' => 'starts_at'],
                ['data' => 'reference', 'name' => 'reference'],
                ['data' => 'customer_contact', 'orderable' => false, 'searchable' => false],
                ['data' => 'service', 'orderable' => false, 'searchable' => false],
                ['data' => 'request_notes', 'name' => 'request_notes'],
                ['data' => 'status_label', 'name' => 'status'],
                ...($customerView ? [] : [['data' => 'management', 'orderable' => false, 'searchable' => false]]),
            ]) }}">
            <thead><tr><th>When</th><th>Reference</th><th>Customer</th><th>Service</th><th>Request</th><th>Status</th>@unless ($customerView)<th>Status / assignee</th>@endunless</tr></thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
    <div class="modal fade" id="booking-filters-modal" tabindex="-1" aria-labelledby="booking-filters-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('bookings.index') }}">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="booking-filters-title">Filter bookings</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="booking-filter-status">Status</label>
                        <select class="form-select" id="booking-filter-status" name="filter[status]">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(($bookingFilters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="booking-filter-service">Service type</label>
                        <select class="form-select" id="booking-filter-service" name="filter[service]">
                            <option value="">All services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service }}" @selected(($bookingFilters['service'] ?? '') === $service)>{{ $service }}</option>
                            @endforeach
                        </select>
                    </div>
                    @unless ($customerView)
                        <div class="mb-3">
                            <label class="form-label" for="booking-filter-customer-name">Customer name</label>
                            <input class="form-control" id="booking-filter-customer-name" name="filter[customer_name]" value="{{ $bookingFilters['customer_name'] ?? '' }}" placeholder="Search name">
                        </div>
                    @endunless
                    <div class="mb-3">
                        <label class="form-label" for="booking-filter-starts-from">From</label>
                        <input class="form-control" id="booking-filter-starts-from" name="filter[starts_from]" type="datetime-local" value="{{ $bookingFilters['starts_from'] ?? '' }}">
                    </div>
                    <div>
                        <label class="form-label" for="booking-filter-starts-to">To</label>
                        <input class="form-control" id="booking-filter-starts-to" name="filter[starts_to]" type="datetime-local" value="{{ $bookingFilters['starts_to'] ?? '' }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection