@extends('layouts.app')

@section('title', 'Repair Tickets')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="Repair tickets">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('tickets.create') }}">
                <span class="fas fa-plus me-2"></span>New ticket
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('tickets.index') }}">
        <div class="col-sm-5 col-md-4">
            <label class="form-label" for="status">Ticket status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-5 col-md-4">
            <label class="form-label" for="assigned">Assignment</label>
            <select class="form-select" id="assigned" name="assigned">
                <option value="">All tickets</option>
                <option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>
                <option value="mine" @selected(request('assigned') === 'mine')>Assigned to me</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary" type="submit">Apply filters</button>
            <a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Reset</a>
        </div>
    </form>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable
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
</div>
@endsection
