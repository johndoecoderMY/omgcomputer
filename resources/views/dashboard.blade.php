@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Workspace" title="Dashboard"
            description="A live overview of your projects, repair queue, schedules, and working examples.">
            <x-slot:actions>
                <a class="btn btn-outline-primary" href="{{ route('projects.calendar') }}">
                    <i class="fas fa-calendar-days me-2" aria-hidden="true"></i>Calendar
                </a>
                <a class="btn btn-primary" href="{{ route('projects.create') }}">
                    <i class="fas fa-plus me-2" aria-hidden="true"></i>New project
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status') === 'initial-password-updated')
            <div class="alert alert-success" role="status">Your password has been updated. You can now continue to your
                workspace.</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card h-md-100">
                    <div class="card-header pb-0">
                        <h4 class="mb-0 mt-2 d-flex align-items-center text-primary">Projects</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-end">
                        <div class="row align-items-end g-2">
                            <div class="col">
                                <p class="font-sans-serif lh-1 mb-1 fs-5">{{ $projectCount }}</p>
                                <span class="small text-body-secondary">Active workspaces</span>
                            </div>
                            <div class="col-auto ps-0">
                                <a class="btn btn-sm btn-primary" href="{{ route('projects.index') }}">View projects</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-md-100">
                    <div class="card-header pb-0">
                        <h4 class="mb-0 mt-2 d-flex align-items-center text-primary">Open tasks</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-end">
                        <div class="row align-items-end g-2">
                            <div class="col">
                                <p class="font-sans-serif lh-1 mb-1 fs-5">{{ $openTaskCount }}</p>
                                <span class="small {{ $overdueTaskCount ? 'text-danger' : 'text-body-secondary' }}">
                                    {{ $overdueTaskCount }} overdue · {{ $assignedTaskCount }} assigned to you
                                </span>
                            </div>
                            <div class="col-auto ps-0">
                                <a class="btn btn-sm btn-primary" href="{{ route('tasks.index') }}">View tasks</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-md-100">
                    <div class="card-header pb-0">
                        <h4 class="mb-0 mt-2 d-flex align-items-center text-primary">Repair queue</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-end">
                        <div class="row align-items-end g-2">
                            <div class="col">
                                <p class="font-sans-serif lh-1 mb-1 fs-5">{{ $openTicketCount }}</p>
                                <span class="small text-body-secondary">{{ $urgentTicketCount }} high priority</span>
                            </div>
                            <div class="col-auto ps-0">
                                <a class="btn btn-sm btn-primary" href="{{ route('tickets.index') }}">View tickets</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-md-100">
                    <div class="card-header pb-0">
                        <h4 class="mb-0 mt-2 d-flex align-items-center text-primary">Unread notifications</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-end">
                        <div class="row align-items-end g-2">
                            <div class="col">
                                <p class="font-sans-serif lh-1 mb-1 fs-5">{{ $unreadNotificationCount }}</p>
                                <span class="small text-body-secondary">Task and project updates</span>
                            </div>
                            <div class="col-auto ps-0">
                                <a class="btn btn-sm btn-primary" href="{{ route('notifications.index') }}">Open inbox</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h4 class="mb-0 text-primary">Projects</h4>
                            <p class="small text-body-secondary mb-0">Progress across recently updated work.</p>
                        </div>
                        <a class="btn btn-sm btn-primary" href="{{ route('projects.index') }}">All projects</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($projects as $project)
                            <a class="list-group-item list-group-item-action px-4 py-3"
                                href="{{ route('projects.show', $project) }}">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                    <span class="fw-semibold text-body">{{ $project->name }}</span>
                                    <span class="small text-body-secondary">{{ $project->progress }}%</span>
                                </div>
                                <div class="progress" role="progressbar" aria-label="{{ $project->name }} progress"
                                    aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100"
                                    style="height:.45rem">
                                    <div class="progress-bar bg-primary" style="width:{{ $project->progress }}%"></div>
                                </div>
                                <span class="small text-body-secondary d-block mt-2">
                                    {{ $project->completed_tasks_count }} of {{ $project->tasks_count }} tasks complete
                                </span>
                            </a>
                        @empty
                            <div class="card-body text-center py-5">
                                <p class="text-body-secondary">No projects have been created yet.</p>
                                <a class="btn btn-primary" href="{{ route('projects.create') }}">Create a project</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h4 class="mb-0 text-primary">Upcoming events</h4>
                            <p class="small text-body-secondary mb-0">Your next scheduled project sessions.</p>
                        </div>
                        <a class="btn btn-sm btn-primary" href="{{ route('projects.calendar') }}">Calendar</a>
                    </div>
                    <div class="card-body">
                        @forelse ($upcomingEvents as $event)
                            <article class="d-flex gap-3 border-bottom py-3">
                                <time class="text-center text-body-secondary"
                                    datetime="{{ $event->starts_at->toIso8601String() }}">
                                    <span class="d-block small text-uppercase">{{ $event->starts_at->format('M') }}</span>
                                    <strong class="fs-5 text-body">{{ $event->starts_at->format('j') }}</strong>
                                </time>
                                <div class="min-w-0">
                                    <h3 class="h6 mb-1">{{ $event->title }}</h3>
                                    <span class="small text-body-secondary">
                                        {{ $event->starts_at->format('D, g:i A') }}
                                        @if ($event->project) · {{ $event->project->name }}@endif
                                    </span>
                                </div>
                            </article>
                        @empty
                            <div class="text-center py-4">
                                <p class="small text-body-secondary">No upcoming sessions.</p>
                                <a class="btn btn-sm btn-primary" href="{{ route('projects.calendar') }}">Schedule an event</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h4 class="mb-0 text-primary">Repair tickets</h4>
                            <p class="small text-body-secondary mb-0">Recently received cases in the active queue.</p>
                        </div>
                        <a class="btn btn-sm btn-primary" href="{{ route('tickets.index') }}">All tickets</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Customer / device</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTickets as $ticket)
                                    <tr>
                                        <td><a class="fw-semibold"
                                                href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                                        </td>
                                        <td>
                                            <span class="d-block">{{ $ticket->customer->name }}</span>
                                            <span
                                                class="small text-body-secondary">{{ $ticket->device_model ?: $ticket->device_type ?: 'Device not specified' }}</span>
                                        </td>
                                        <td><span
                                                class="badge text-bg-light border">{{ \App\Models\RepairTicket::STATUSES[$ticket->status] ?? $ticket->status }}</span>
                                        </td>
                                        <td><span
                                                class="badge {{ in_array($ticket->priority, ['high', 'urgent'], true) ? 'text-bg-warning' : 'text-bg-light border' }}">{{ ucfirst($ticket->priority) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-body-secondary py-4">The repair queue is clear.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h4 class="mb-0 text-primary">SOP &amp; technical examples</h4>
                            <p class="small text-body-secondary mb-0">Reusable web, database, and service structures.</p>
                        </div>
                        <a class="btn btn-sm btn-primary" href="{{ route('development.sops') }}">Browse library</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($sopExamples as $example)
                            <a class="list-group-item list-group-item-action px-4 py-3"
                                href="{{ route('development.flowcharts.show', $example) }}">
                                <span class="badge text-bg-light border mb-2">{{ ucfirst($example->category) }}</span>
                                <span class="d-block fw-semibold text-body">{{ $example->title }}</span>
                                @if ($example->description)
                                    <span
                                        class="small text-body-secondary">{{ \Illuminate\Support\Str::limit($example->description, 100) }}</span>
                                @endif
                            </a>
                        @empty
                            <div class="card-body text-body-secondary">No examples have been seeded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection