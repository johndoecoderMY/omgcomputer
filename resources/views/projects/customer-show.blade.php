@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="My project" :title="$project->name"
        :description="$project->description"
        :back-url="route('projects.index')" back-label="My projects" />

    <div class="card mb-4">        <div class="card-header bg-body">
            <h2 class="h5 mb-0">Your repair tickets for this project</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Ticket</th><th>Device</th><th>Status</th><th>Received</th></tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="fw-semibold">{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->device_model ?: $ticket->device_type ?: '—' }}</td>
                            <td>{{ \App\Models\RepairTicket::STATUSES[$ticket->status] ?? $ticket->status }}</td>
                            <td>{{ $ticket->received_at->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-body-secondary py-4">No repair tickets are linked to this project.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
