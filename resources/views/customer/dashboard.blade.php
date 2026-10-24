@extends('layouts.app')

@section('title', 'My dashboard')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="My workspace" title="Welcome, {{ auth()->user()->name }}"
        description="A quick overview of your projects, repair tickets, and upcoming appointments.">
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('bookings.account-create') }}"><i class="fas fa-calendar-plus me-2" aria-hidden="true"></i>Book a service</a>
            <a class="btn btn-outline-primary" href="{{ route('shop.preview.products.grid') }}"><i class="fas fa-store me-2" aria-hidden="true"></i>Browse shop</a>
            <a class="btn btn-outline-secondary" href="{{ route('contact') }}"><i class="fas fa-headset me-2" aria-hidden="true"></i>Contact us</a>
        </x-slot>
    </x-page-header>

    @if ($unreadNotificationCount > 0)
        <div class="alert alert-primary d-flex align-items-center justify-content-between gap-3" role="status">
            <span><i class="fas fa-bell me-2" aria-hidden="true"></i>You have {{ $unreadNotificationCount }} unread {{ \Illuminate\Support\Str::plural('notification', $unreadNotificationCount) }}.</span>
            <a class="alert-link text-nowrap" href="{{ route('notifications.index') }}">View notifications</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <a class="card h-100 text-decoration-none" href="{{ route('projects.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar avatar-xl"><span class="avatar-name d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary"><span class="fas fa-project-diagram" aria-hidden="true"></span></span></span>
                    <div><p class="text-body-secondary mb-1">My projects</p><h2 class="h3 mb-0 text-body">{{ $projectCount }}</h2></div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-4">
            <a class="card h-100 text-decoration-none" href="{{ route('tickets.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar avatar-xl"><span class="avatar-name d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning"><span class="fas fa-screwdriver-wrench" aria-hidden="true"></span></span></span>
                    <div><p class="text-body-secondary mb-1">Active repair tickets</p><h2 class="h3 mb-0 text-body">{{ $activeTicketCount }}</h2></div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-4">
            <a class="card h-100 text-decoration-none" href="{{ route('bookings.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar avatar-xl"><span class="avatar-name d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success"><span class="fas fa-calendar-check" aria-hidden="true"></span></span></span>
                    <div><p class="text-body-secondary mb-1">Upcoming appointments</p><h2 class="h3 mb-0 text-body">{{ $upcomingBookingCount }}</h2></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <div><h2 class="h5 mb-0">Upcoming appointments</h2><p class="small text-body-secondary mb-0">Your next scheduled services.</p></div>
                    <a class="btn btn-sm btn-falcon-default" href="{{ route('bookings.index') }}">All bookings</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($upcomingBookings as $booking)
                        <a class="list-group-item list-group-item-action px-3 py-3" href="{{ route('bookings.show', $booking) }}">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h3 class="h6 mb-1">{{ $booking->service_name }}</h3>
                                    <p class="small text-body-secondary mb-0">{{ $booking->starts_at->format('M j, Y · g:i A') }}</p>
                                </div>
                                <span class="badge text-bg-{{ $booking->status === 'confirmed' ? 'success' : 'warning' }}">{{ \App\Models\Booking::STATUSES[$booking->status] ?? ucfirst($booking->status) }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-center">
                            <p class="text-body-secondary mb-3">You have no upcoming appointments.</p>
                            <a class="btn btn-sm btn-primary" href="{{ route('bookings.account-create') }}">Book a service</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <div><h2 class="h5 mb-0">Repair updates</h2><p class="small text-body-secondary mb-0">The latest status of your devices.</p></div>
                    <a class="btn btn-sm btn-falcon-default" href="{{ route('tickets.index') }}">All tickets</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($recentTickets as $ticket)
                        <a class="list-group-item list-group-item-action px-3 py-3" href="{{ route('tickets.show', $ticket) }}">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h3 class="h6 mb-1">{{ $ticket->ticket_number }} <span class="text-body-secondary fw-normal">· {{ $ticket->device_model ?: $ticket->device_type }}</span></h3>
                                    <p class="small text-body-secondary mb-0">Updated {{ $ticket->updated_at->diffForHumans() }}</p>
                                </div>
                                <span class="badge text-bg-primary">{{ \App\Models\RepairTicket::STATUSES[$ticket->status] ?? ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-center">
                            <p class="text-body-secondary mb-0">No repair tickets are linked to your account yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <div><h2 class="h5 mb-0">My projects</h2><p class="small text-body-secondary mb-0">Projects connected to your customer account.</p></div>
                    <a class="btn btn-sm btn-falcon-default" href="{{ route('projects.index') }}">All projects</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Project</th><th>Description</th><th class="text-end">Details</th></tr></thead>
                        <tbody>
                            @forelse ($projects as $project)
                                <tr>
                                    <td class="fw-semibold">{{ $project->name }}</td>
                                    <td class="text-body-secondary">{{ \Illuminate\Support\Str::limit($project->description ?: 'No project summary available.', 140) }}</td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('projects.show', $project) }}">View project</a></td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-body-secondary py-4" colspan="3">No projects are linked to your account yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
