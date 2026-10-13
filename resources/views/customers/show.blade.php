@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Customer history" title="{{ $customer->name }}" description="{{ $customer->phone ?? 'No phone recorded' }}{{ $customer->email ? ' · '.$customer->email : '' }}" :breadcrumbs="[['label' => 'Customers', 'url' => route('customers.index')], ['label' => $customer->name]]" :back-url="route('customers.index')" back-label="Back to customers">
            <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('tickets.create', ['customer' => $customer->id]) }}"><span class="fas fa-plus me-2"></span>New repair ticket</a>
            </x-slot:actions>
        </x-page-header>
        <section class="card"><div class="card-header"><h2 class="h5 mb-0">Repair history <span class="badge badge-subtle-primary ms-2">{{ $customer->tickets->count() }}</span></h2></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Ticket</th><th>Device / issue</th><th>Status</th><th>Priority</th><th>Technician</th><th>Opened</th></tr></thead>
                <tbody>@forelse ($customer->tickets as $ticket)
                    <tr><td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td><td>{{ $ticket->device_type }}{{ $ticket->device_model ? ' · '.$ticket->device_model : '' }}<div class="text-600">{{ $ticket->subject }}</div></td><td>{{ $statuses[$ticket->status] ?? ucfirst(str_replace('_', ' ', $ticket->status)) }}</td><td>{{ ucfirst($ticket->priority) }}</td><td>{{ $ticket->assignee->name ?? 'Unassigned' }}</td><td>{{ $ticket->created_at->format('M j, Y') }}</td></tr>
                @empty<tr><td colspan="6" class="text-center text-600 py-4">No repair history recorded.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    </div>
@endsection
