@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="Tickets">
        @unless (auth()->user()->hasRole('customer'))
            <x-slot:actions>
                <a class="btn btn-primary" href="{{ route('tickets.create') }}">
                    <span class="fas fa-plus me-2"></span>New ticket
                </a>
            </x-slot:actions>
        @endunless
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card p-2">
        <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#ticket-filters-modal">
                <span class="fas fa-filter me-2" aria-hidden="true"></span>Filters
                @if (request()->filled('status') || request()->filled('assigned'))
                    <span class="badge text-bg-primary ms-1">{{ (int) request()->filled('status') + (int) request()->filled('assigned') }}</span>
                @endif
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
                data-source="{{ route('tickets.datatable', request()->only(['status', 'assigned'])) }}"
                data-columns="{{ json_encode([
                    ['data' => 'ticket', 'name' => 'ticket_number', 'orderable' => true, 'searchable' => true],
                    ['data' => 'customer_name', 'name' => 'customer_name', 'orderable' => false, 'searchable' => true],
                    ['data' => 'device', 'name' => 'device', 'orderable' => false, 'searchable' => true],
                    ['data' => 'project_name', 'name' => 'project_name', 'orderable' => false, 'searchable' => true],
                    ['data' => 'status_label', 'name' => 'status', 'orderable' => true, 'searchable' => false],
                    ['data' => 'priority_label', 'name' => 'priority', 'orderable' => true, 'searchable' => false],
                    ['data' => 'assignee_name', 'name' => 'assignee_name', 'orderable' => false, 'searchable' => true],
                    ['data' => 'due_date', 'name' => 'due_at', 'orderable' => true, 'searchable' => false],
                    ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead>
                    <tr>
                        <th>Ticket</th><th>Customer</th><th>Device</th><th>Project</th>
                        <th>Status</th><th>Priority</th><th>Assigned to</th><th>Due</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="ticket-filters-modal" tabindex="-1" aria-labelledby="ticket-filters-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('tickets.index') }}">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="ticket-filters-title">Filter tickets</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="ticket-filter-status">Ticket status</label>
                        <select class="form-select" id="ticket-filter-status" name="status">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @unless (auth()->user()->hasRole('customer'))
                        <div>
                            <label class="form-label" for="ticket-filter-assigned">Assignment</label>
                            <select class="form-select" id="ticket-filter-assigned" name="assigned">
                                <option value="">All tickets</option>
                                <option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>
                                <option value="mine" @selected(request('assigned') === 'mine')>Assigned to me</option>
                            </select>
                        </div>
                    @endunless
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
