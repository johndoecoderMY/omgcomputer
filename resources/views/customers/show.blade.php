@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customer profile" :title="$customer->name" :back-url="route('customers.index')" back-label="All customers">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('tickets.create', ['customer_id' => $customer->id, 'project_id' => $customer->project_id]) }}">
                <span class="bi bi-ticket-perforated me-2" aria-hidden="true"></span>New repair ticket
            </a>
            <a class="btn btn-primary" href="{{ route('customers.edit', $customer) }}">
                <span class="bi bi-pencil me-2" aria-hidden="true"></span>Edit profile
            </a>
        </x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Contact details</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $customer->email ?: '—' }}</dd>
                        <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $customer->phone ?: '—' }}</dd>
                        <dt class="col-sm-4">Company</dt><dd class="col-sm-8">{{ $customer->company ?: '—' }}</dd>
                        <dt class="col-sm-4">Address</dt><dd class="col-sm-8 text-pre-line">{{ $customer->address ?: '—' }}</dd>
                        <dt class="col-sm-4">Project</dt>
                        <dd class="col-sm-8">
                            @if ($customer->project)
                                <a href="{{ route('projects.show', $customer->project) }}">{{ $customer->project->name }}</a>
                            @else
                                <span class="text-body-secondary">Not linked</span>
                            @endif
                        </dd>
                    </dl>
                    @if ($customer->notes)
                        <hr>
                        <div class="small text-body-secondary">Profile notes</div>
                        <p class="mb-0">{{ $customer->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <span class="fw-semibold">Repair history</span>
                    <span class="badge text-bg-light border ms-auto">{{ $customer->repairTickets->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-bordered">
                        <thead><tr><th>Ticket</th><th>Device</th><th>Assigned to</th><th>Status</th><th>Received</th></tr></thead>
                        <tbody>
                            @forelse ($customer->repairTickets as $ticket)
                                <tr>
                                    <td><a class="fw-semibold" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td>
                                    <td>{{ $ticket->device_model ?: $ticket->device_type ?: 'Device not specified' }}</td>
                                    <td>{{ $ticket->assignees->pluck('name')->join(', ') ?: 'Unassigned' }}</td>
                                    <td><x-status-badge :status="$ticket->status" :label="\App\Models\RepairTicket::STATUSES[$ticket->status] ?? $ticket->status" /></td>
                                    <td>{{ $ticket->received_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-4">No repair tickets for this customer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <section class="card mb-3" aria-labelledby="customer-projects-heading">
        <div class="card-header">
            <h2 class="h5 mb-0" id="customer-projects-heading">Projects</h2>
            <span class="badge text-bg-light border ms-auto">{{ $linkedProjects->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-bordered">
                <thead>
                    <tr><th>Project</th><th>Customer link</th><th>Related repair tickets</th></tr>
                </thead>
                <tbody>
                    @forelse ($linkedProjects as $project)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                            <td>
                                @if ($customer->project_id === $project->id)
                                    <span class="badge text-bg-primary">Customer profile</span>
                                @endif
                                @if ($project->repairTickets->isNotEmpty())
                                    <span class="badge text-bg-light border">Repair tickets</span>
                                @endif
                            </td>
                            <td>
                                @forelse ($project->repairTickets as $ticket)
                                    <a class="me-2" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                                @empty
                                    <span class="text-body-secondary">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-body-secondary py-4">No projects are linked to this customer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if ($account)
        <section class="card mb-3" aria-labelledby="customer-account-heading">
            <div class="card-header">
                <h2 class="h5 mb-0" id="customer-account-heading">Customer login account</h2>
                @if (auth()->user()->hasRole('admin'))
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $account) }}">Manage account</a>
                @endif
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Account name</dt><dd class="col-sm-9">{{ $account->name }}</dd>
                    <dt class="col-sm-3">Login email</dt><dd class="col-sm-9">{{ $account->email }}</dd>
                    <dt class="col-sm-3">Email verification</dt>
                    <dd class="col-sm-9">
                        @if ($account->hasVerifiedEmail())
                            <span class="badge text-bg-success">Verified</span>
                        @else
                            <span class="badge text-bg-warning">Not verified</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </section>
    @elseif ($customer->email)
        <div class="alert alert-light border" role="status">No customer login account is linked to this email address.</div>
    @endif
</div>
@endsection