@extends('layouts.app')

@section('title', 'Repair Support Desk')

@section('content')
    <div class="container-fluid py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Repair support desk</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <p class="text-primary fw-bold text-uppercase fs-10 mb-1">Customer service</p>
                <h1 class="h2 mb-1">Repair support desk</h1>
                <p class="text-700 mb-0">Track customer devices from intake through diagnosis, repair, and pickup.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTicketModal"><span class="fas fa-plus me-2"></span>Create repair ticket</button>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('tickets.index') }}"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Open tickets</p><p class="h3 mb-0">{{ $counts['open'] }}</p></div></a></div>
            <div class="col-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('tickets.index', ['status' => 'waiting_parts']) }}"><div class="card-body"><p class="text-warning text-uppercase fs-10 fw-bold mb-2">Waiting for parts</p><p class="h3 text-warning mb-0">{{ $counts['waiting_parts'] }}</p></div></a></div>
            <div class="col-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('tickets.index', ['status' => 'ready_for_pickup']) }}"><div class="card-body"><p class="text-success text-uppercase fs-10 fw-bold mb-2">Ready for pickup</p><p class="h3 text-success mb-0">{{ $counts['ready'] }}</p></div></a></div>
            <div class="col-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('tickets.index', ['assigned' => 'unassigned']) }}"><div class="card-body"><p class="text-danger text-uppercase fs-10 fw-bold mb-2">Unassigned</p><p class="h3 text-danger mb-0">{{ $counts['unassigned'] }}</p></div></a></div>
        </div>

        <section class="card" id="ticketsTable">
            <div class="card-header border-bottom border-200">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1">All repair tickets</h2>
                        <p class="text-600 fs-10 mb-0">Search by customer, ticket number, subject, or device model.</p>
                    </div>
                    <button class="btn btn-sm btn-falcon-default" type="button" data-bs-toggle="modal" data-bs-target="#ticketFilterModal"><span class="fas fa-filter me-1"></span>Filters</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-hover mb-0 fs-10" data-datatables='{"pageLength":10,"order":[[0,"desc"]],"language":{"emptyTable":"No repair tickets match these filters."}}'>
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="ps-3">Ticket / Customer</th>
                                <th style="min-width: 15rem">Device / Issue</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Technician</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                @php
                                    $statusTone = match ($ticket->status) {
                                        'closed' => 'secondary',
                                        'ready_for_pickup' => 'success',
                                        'waiting_parts', 'waiting_customer' => 'warning',
                                        'in_progress' => 'primary',
                                        default => 'info',
                                    };
                                    $priorityTone = match ($ticket->priority) {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'low' => 'secondary',
                                        default => 'info',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <a class="fw-semi-bold" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                                        <div class="text-700">{{ $ticket->customer_name }}</div>
                                        @if ($ticket->customer_phone)<small class="text-600">{{ $ticket->customer_phone }}</small>@endif
                                    </td>
                                    <td>
                                        <a class="fw-semi-bold" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a>
                                        <div class="text-600">{{ $ticket->device_type }}@if($ticket->device_model) · {{ $ticket->device_model }}@endif</div>
                                    </td>
                                    <td><span class="badge rounded-pill badge-subtle-{{ $statusTone }}">{{ $statuses[$ticket->status] }}</span></td>
                                    <td><span class="badge rounded-pill badge-subtle-{{ $priorityTone }}">{{ $priorities[$ticket->priority] }}</span></td>
                                    <td>{{ $ticket->assignee->name ?? 'Unassigned' }}</td>
                                    <td>{{ $ticket->due_date?->format('M j, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade" id="ticketFilterModal" tabindex="-1" aria-labelledby="ticketFilterModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="GET" action="{{ route('tickets.index') }}">
            <div class="modal-header"><h2 class="modal-title fs-5" id="ticketFilterModalLabel">Filter repair tickets</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="ticket-search">Search</label><input class="form-control" id="ticket-search" name="search" value="{{ $search }}" placeholder="Customer, ticket number, issue, or model"></div>
                <div class="mb-3"><label class="form-label" for="ticket-status">Status</label><select class="form-select" id="ticket-status" name="status"><option value="">All statuses</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label" for="ticket-priority">Priority</label><select class="form-select" id="ticket-priority" name="priority"><option value="">All priorities</option>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected($priorityFilter === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="form-label" for="ticket-assigned">Technician assignment</label><select class="form-select" id="ticket-assigned" name="assigned"><option value="">Anyone</option><option value="unassigned" @selected($assignedFilter === 'unassigned')>Unassigned</option><option value="mine" @selected($assignedFilter === 'mine')>Assigned to me</option></select></div>
            </div>
            <div class="modal-footer"><a class="btn btn-falcon-default me-auto" href="{{ route('tickets.index') }}">Clear filters</a><button class="btn btn-primary" type="submit">Apply filters</button></div>
        </form>
    </div></div></div>
    <div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <form method="POST" action="{{ route('tickets.store') }}">
            @csrf
            <input type="hidden" name="ui_modal" value="createTicketModal">
            <div class="modal-header"><h2 class="modal-title fs-5" id="createTicketModalLabel">Create repair ticket</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <h3 class="h6">Customer details</h3><div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label" for="customer-name">Customer name</label><input class="form-control" id="customer-name" name="customer_name" value="{{ old('customer_name') }}" maxlength="180" required></div>
                    <div class="col-md-6"><label class="form-label" for="customer-phone">Phone</label><input class="form-control" id="customer-phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50"></div>
                    <div class="col-12"><label class="form-label" for="customer-email">Email</label><input class="form-control" id="customer-email" name="customer_email" type="email" value="{{ old('customer_email') }}" maxlength="255"></div>
                </div>
                <h3 class="h6">Device and reported issue</h3><div class="row g-3 mb-4">
                    <div class="col-md-4"><label class="form-label" for="device-type">Device type</label><select class="form-select" id="device-type" name="device_type" required><option value="">Choose device</option>@foreach (['Laptop', 'Desktop PC', 'All-in-one PC', 'Other'] as $device)<option value="{{ $device }}" @selected(old('device_type') === $device)>{{ $device }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label" for="device-model">Make / model</label><input class="form-control" id="device-model" name="device_model" value="{{ old('device_model') }}" maxlength="180"></div>
                    <div class="col-md-4"><label class="form-label" for="serial-number">Serial number</label><input class="form-control" id="serial-number" name="serial_number" value="{{ old('serial_number') }}" maxlength="120"></div>
                    <div class="col-12"><label class="form-label" for="subject">Issue summary</label><input class="form-control" id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" required></div>
                    <div class="col-12"><label class="form-label" for="issue-description">Customer's description</label><textarea class="form-control" id="issue-description" name="issue_description" rows="4" maxlength="5000" required>{{ old('issue_description') }}</textarea></div>
                </div>
                <h3 class="h6">Ticket details</h3><div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority" required>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label" for="assigned-to">Assign technician</label><select class="form-select" id="assigned-to" name="assigned_to"><option value="">Unassigned</option>@foreach ($teamMembers as $member)<option value="{{ $member->id }}" @selected((string) old('assigned_to') === (string) $member->id)>{{ $member->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label" for="due-date">Target completion date</label><input class="form-control" id="due-date" name="due_date" type="date" value="{{ old('due_date') }}"></div>
                </div>
                <div class="alert alert-info fs-10 mt-3 mb-0">The ticket starts in <strong>Received</strong>. Add diagnosis, estimate, and status updates from its ticket page.</div>
            </div>
            <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit"><span class="fas fa-ticket-alt me-2"></span>Create ticket</button></div>
        </form>
    </div></div></div>
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>
    @endpush
@endsection
