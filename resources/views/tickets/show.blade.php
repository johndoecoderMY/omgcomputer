@extends('layouts.app')

@section('title', $ticket->ticket_number)

@section('content')
    <div class="container-fluid py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Repair support desk</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $ticket->ticket_number }}</li>
            </ol>
        </nav>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <p class="text-primary fw-bold text-uppercase fs-10 mb-1">Repair ticket</p>
                <h1 class="h2 mb-2">{{ $ticket->ticket_number }}</h1>
                <span class="badge rounded-pill badge-subtle-primary">{{ $statuses[$ticket->status] }}</span>
                <span class="badge rounded-pill badge-subtle-warning">{{ $priorities[$ticket->priority] }} priority</span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-falcon-default" href="{{ route('tickets.index') }}"><span class="fas fa-arrow-left me-2"></span>All tickets</a>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#updateTicketModal"><span class="fas fa-edit me-2"></span>Update ticket</button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <section class="card mb-4">
                    <div class="card-header"><h2 class="h5 mb-0">{{ $ticket->subject }}</h2></div>
                    <div class="card-body">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Customer</p><h3 class="h6 mb-1">{{ $ticket->customer_name }}</h3>@if($ticket->customer_phone)<p class="mb-1">{{ $ticket->customer_phone }}</p>@endif @if($ticket->customer_email)<p class="mb-0">{{ $ticket->customer_email }}</p>@endif</div>
                            <div class="col-md-6"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Device</p><h3 class="h6 mb-1">{{ $ticket->device_type }}{{ $ticket->device_model ? ' · '.$ticket->device_model : '' }}</h3><p class="mb-0">Serial number: {{ $ticket->serial_number ?: 'Not recorded' }}</p></div>
                        </div>
                        <h3 class="h6">Customer-reported issue</h3>
                        <p class="text-700 mb-4" style="white-space: pre-line">{{ $ticket->issue_description }}</p>
                        <div class="border-top pt-3">
                            <h3 class="h6">Diagnosis and repair notes</h3>
                            @if ($ticket->diagnostic_notes)
                                <p class="text-700 mb-0" style="white-space: pre-line">{{ $ticket->diagnostic_notes }}</p>
                            @else
                                <p class="text-600 mb-0">No diagnosis recorded yet.</p>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer text-600 fs-10">Created by {{ $ticket->creator->name }} on {{ $ticket->created_at->format('M j, Y · H:i') }}@if($ticket->due_date) · Target date {{ $ticket->due_date->format('M j, Y') }}@endif</div>
                </section>
            </div>
            <div class="col-xl-4">
                <section class="card">
                    <div class="card-body">
                        <h2 class="h5">Ticket details</h2>
                        <dl class="row mb-0 fs-10"><dt class="col-5 text-600">Technician</dt><dd class="col-7">{{ $ticket->assignee->name ?? 'Unassigned' }}</dd><dt class="col-5 text-600">Estimate</dt><dd class="col-7">{{ $ticket->quoted_amount !== null ? 'RM'.number_format((float) $ticket->quoted_amount, 2) : 'Not set' }}</dd><dt class="col-5 text-600">Target date</dt><dd class="col-7">{{ $ticket->due_date?->format('M j, Y') ?? 'Not set' }}</dd></dl>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <div class="modal fade" id="updateTicketModal" tabindex="-1" aria-labelledby="updateTicketModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                        <form method="POST" action="{{ route('tickets.update', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="ui_modal" value="updateTicketModal">
                            <div class="modal-header"><h2 class="modal-title fs-5" id="updateTicketModalLabel">Update {{ $ticket->ticket_number }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body">
                            <div class="mb-3"><label class="form-label" for="ticket-status">Status</label><select class="form-select" id="ticket-status" name="status" required>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label" for="ticket-priority">Priority</label><select class="form-select" id="ticket-priority" name="priority" required>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', $ticket->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label" for="ticket-assignee">Technician</label><select class="form-select" id="ticket-assignee" name="assigned_to"><option value="">Unassigned</option>@foreach ($teamMembers as $member)<option value="{{ $member->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $member->id)>{{ $member->name }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label" for="quoted-amount">Estimate (RM)</label><input class="form-control" id="quoted-amount" name="quoted_amount" type="number" min="0" step="0.01" value="{{ old('quoted_amount', $ticket->quoted_amount) }}"></div>
                            <div class="mb-3"><label class="form-label" for="ticket-due-date">Target completion date</label><input class="form-control" id="ticket-due-date" name="due_date" type="date" value="{{ old('due_date', $ticket->due_date?->toDateString()) }}"></div>
                            <div class="mb-3"><label class="form-label" for="diagnostic-notes">Diagnosis / repair notes</label><textarea class="form-control" id="diagnostic-notes" name="diagnostic_notes" rows="6" maxlength="5000">{{ old('diagnostic_notes', $ticket->diagnostic_notes) }}</textarea></div>
                            </div>
                            <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save ticket update</button></div>
                        </form>
    </div></div></div>
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>
    @endpush
@endsection
