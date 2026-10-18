@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customer profile" :title="$customer->name" :back-url="route('customers.index')" back-label="All customers">
        <x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('tickets.create', ['customer_id' => $customer->id, 'project_id' => $customer->project_id]) }}"><span class="fas fa-ticket-alt me-2"></span>New repair ticket</a><a class="btn btn-primary" href="{{ route('customers.edit', $customer) }}"><span class="fas fa-pen me-2"></span>Edit profile</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row g-3 mb-3"><div class="col-lg-5"><div class="card h-100"><div class="card-header fw-semibold">Contact details</div><div class="card-body"><dl class="row mb-0"><dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $customer->email ?: '—' }}</dd><dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $customer->phone ?: '—' }}</dd><dt class="col-sm-4">Company</dt><dd class="col-sm-8">{{ $customer->company ?: '—' }}</dd><dt class="col-sm-4">Address</dt><dd class="col-sm-8 text-pre-line">{{ $customer->address ?: '—' }}</dd><dt class="col-sm-4">Project</dt><dd class="col-sm-8">@if ($customer->project)<a href="{{ route('projects.show', $customer->project) }}">{{ $customer->project->name }}</a>@else<span class="text-body-secondary">Not linked</span>@endif</dd></dl>@if ($customer->notes)<hr><div class="small text-body-secondary">Profile notes</div><p class="mb-0">{{ $customer->notes }}</p>@endif</div></div></div>
        <div class="col-lg-7"><div class="card h-100"><div class="card-header d-flex justify-content-between"><span class="fw-semibold">Repair history</span><span class="badge text-bg-light border">{{ $customer->repairTickets->count() }}</span></div><div class="table-responsive"><table class="table align-middle mb-0 table-bordered"><thead><tr><th>Ticket</th><th>Device</th><th>Status</th><th>Received</th></tr></thead><tbody>@forelse ($customer->repairTickets as $ticket)<tr><td><a class="fw-semibold" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td><td>{{ $ticket->device_model ?: $ticket->device_type ?: 'Device not specified' }}</td><td><x-status-badge :status="$ticket->status" :label="\App\Models\RepairTicket::STATUSES[$ticket->status] ?? $ticket->status" /></td><td>{{ $ticket->received_at->format('M j, Y') }}</td></tr>@empty<tr><td colspan="4" class="text-center text-body-secondary py-4">No repair tickets for this customer.</td></tr>@endforelse</tbody></table></div></div></div>
    </div>
</div>
@endsection