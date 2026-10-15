@extends('layouts.app')

@section('title', 'Repair Tickets')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="Repair tickets"><x-slot name="actions"><a class="btn btn-primary" href="{{ route('tickets.create') }}"><span class="fas fa-plus me-2"></span>New ticket</a></x-slot></x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="row g-2 mb-3" method="GET"><div class="col-md-5"><label class="visually-hidden" for="search">Search tickets</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Ticket, device or customer"></div><div class="col-md-4"><label class="visually-hidden" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Reset</a></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Ticket</th><th>Customer</th><th>Device</th><th>Project</th><th>Status</th><th>Priority</th><th>Assigned to</th></tr></thead><tbody>
        @forelse ($tickets as $ticket)<tr><td><a class="fw-semibold" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a><div class="small text-body-secondary">{{ $ticket->received_at->format('M j, Y') }}</div></td><td><a href="{{ route('customers.show', $ticket->customer) }}">{{ $ticket->customer->name }}</a></td><td>{{ $ticket->device_model ?: $ticket->device_type ?: '—' }}</td><td>{{ $ticket->project?->name ?: '—' }}</td><td><span class="badge text-bg-light border">{{ $statuses[$ticket->status] }}</span></td><td>{{ ucfirst($ticket->priority) }}</td><td>{{ $ticket->assignee?->name ?: 'Unassigned' }}</td></tr>
        @empty<tr><td colspan="7" class="text-center text-body-secondary py-5">No repair tickets match these filters.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $tickets->links() }}</div></div>
</div>
@endsection