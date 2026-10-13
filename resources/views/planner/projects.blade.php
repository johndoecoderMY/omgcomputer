@extends('layouts.app')

@section('title', $project->name.' Overview')

@section('content')
    @php
        $taskBase = ['project' => $project->id];
    @endphp
    <div class="container-fluid py-3">
        <x-page-header
            eyebrow="Planning & progress"
            title="{{ $project->name }}"
            description="{{ $isMainProject ? 'One project for the website, repair work, and everything needed to run the shop.' : ($project->description ?: 'Plan tasks, track progress, and keep project discussions together.') }}"
            :breadcrumbs="[['label' => $project->name, 'url' => route('projects.index')], ['label' => 'Overview']]"
            :back-url="route('projects.index')"
            back-label="Back to projects"
        >
            <x-slot:actions>
                @if ($isMainProject)<a class="btn btn-falcon-default" href="{{ route('tickets.index') }}"><span class="fas fa-ticket-alt me-2"></span>Support desk</a>@endif
                <a class="btn btn-primary" href="{{ route('planner.index', $taskBase + ['view' => 'kanban']) }}"><span class="fas fa-columns me-2"></span>Open task board</a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="card mb-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar avatar-3xl">
                                <div class="avatar-name rounded-circle bg-primary text-white"><span class="fas fa-desktop"></span></div>
                            </div>
                            <div>
                                @if ($isMainProject)<span class="badge badge-subtle-primary mb-1">Main project</span>@endif
                                <h2 class="h3 mb-0">{{ $project->name }}</h2>
                            </div>
                        </div>
                        <p class="text-700 mb-0">{{ $project->description }}</p>
                        @if ($project->images)
                            <div class="row g-2 mt-3">
                                @foreach ($project->images as $imageIndex => $image)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <a href="{{ route('projects.images.show', [$project, $imageIndex]) }}" target="_blank" rel="noopener">
                                            <img class="img-fluid rounded" src="{{ route('projects.images.show', [$project, $imageIndex]) }}" alt="Photo for {{ $project->name }}">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-600 text-uppercase fs-10 fw-bold">Overall completion</span>
                            <strong>{{ $totals['percent'] }}%</strong>
                        </div>
                        <div class="progress" role="progressbar" aria-label="{{ $project->name }} project completion" aria-valuenow="{{ $totals['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: {{ $totals['percent'] }}%"></div>
                        </div>
                        <p class="text-600 fs-10 mt-2 mb-0">{{ $totals['done'] }} of {{ $totals['total'] }} visible tasks completed</p>
                    </div>
                </div>
            </div>
        </section>

        @if ($project->visibility === 'team' || $project->user_id === auth()->id())
            <section class="card mb-4">
                <div class="card-body">
                    <h2 class="h5">Project photos</h2>
                    <form method="POST" action="{{ route('projects.images.store', $project) }}" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-3">
                        @csrf
                        <div><label class="form-label" for="project-images">Upload pictures</label><input class="form-control" id="project-images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required><div class="form-text">Up to 5 images, 5 MB each (JPG, PNG, WebP).</div></div>
                        <button class="btn btn-falcon-default" type="submit">Upload pictures</button>
                    </form>
                </div>
            </section>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase) }}">
                    <div class="card-body">
                        <p class="text-600 text-uppercase fs-10 fw-bold mb-2">All tasks</p>
                        <p class="h3 mb-1">{{ $totals['total'] }}</p>
                        <span class="text-600 fs-10">{{ $totals['remaining'] }} still to do</span>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['status' => 'done']) }}">
                    <div class="card-body">
                        <p class="text-success text-uppercase fs-10 fw-bold mb-2">Done</p>
                        <p class="h3 text-success mb-1">{{ $totals['done'] }}</p>
                        <span class="text-600 fs-10">Completed tasks</span>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['status' => 'in_progress']) }}">
                    <div class="card-body">
                        <p class="text-primary text-uppercase fs-10 fw-bold mb-2">In progress</p>
                        <p class="h3 text-primary mb-1">{{ $totals['active'] }}</p>
                        <span class="text-600 fs-10">Being worked on now</span>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['status' => 'blocked']) }}">
                    <div class="card-body">
                        <p class="text-warning text-uppercase fs-10 fw-bold mb-2">Blocked</p>
                        <p class="h3 text-warning mb-1">{{ $totals['blocked'] }}</p>
                        <span class="text-600 fs-10">Waiting on a blocker</span>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['overdue' => 1]) }}">
                    <div class="card-body">
                        <p class="text-danger text-uppercase fs-10 fw-bold mb-2">Overdue</p>
                        <p class="h3 text-danger mb-1">{{ $overdueTasks }}</p>
                        <span class="text-600 fs-10">Past due and not completed</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['type' => 'feature']) }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><p class="text-600 text-uppercase fs-10 fw-bold mb-1">Website features</p><span class="text-600 fs-10">{{ $isMainProject ? 'Build and improve OMG Computer' : 'Build and improve this project' }}</span></div>
                        <span class="h3 text-primary mb-0">{{ $categoryCounts['features'] }}</span>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-3">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['type' => 'operations']) }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><p class="text-600 text-uppercase fs-10 fw-bold mb-1">Shop operations</p><span class="text-600 fs-10">Parts, suppliers, and daily work</span></div>
                        <span class="h3 text-info mb-0">{{ $categoryCounts['operations'] }}</span>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-3">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['type' => 'task']) }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><p class="text-600 text-uppercase fs-10 fw-bold mb-1">General tasks</p><span class="text-600 fs-10">Other project work</span></div>
                        <span class="h3 text-700 mb-0">{{ $categoryCounts['general'] }}</span>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-3">
                <a class="card h-100 text-decoration-none" href="{{ route('planner.index', $taskBase + ['type' => 'milestone']) }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><p class="text-600 text-uppercase fs-10 fw-bold mb-1">Milestones</p><span class="text-600 fs-10">Key goals and launch dates</span></div>
                        <span class="h3 text-warning mb-0">{{ $categoryCounts['milestones'] }}</span>
                    </div>
                </a>
            </div>
        </div>

        @if ($isMainProject)
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <a class="card h-100 text-decoration-none" href="{{ route('tickets.index') }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><p class="text-600 text-uppercase fs-10 fw-bold mb-1">Open repair tickets</p><span class="text-600 fs-10">Customer devices in the support desk</span></div>
                        <span class="h3 mb-0">{{ $tickets['open'] }}</span>
                    </div>
                </a>
            </div>
            <div class="col-sm-6">
                <a class="card h-100 text-decoration-none" href="{{ route('tickets.index', ['priority' => 'urgent']) }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><p class="text-danger text-uppercase fs-10 fw-bold mb-1">Urgent tickets</p><span class="text-600 fs-10">Needs attention from the team</span></div>
                        <span class="h3 text-danger mb-0">{{ $tickets['urgent'] }}</span>
                    </div>
                </a>
            </div>
        </div>
        @endif

        <section class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1">All project tasks</h2>
                    <p class="text-600 fs-10 mb-0">Track and discuss the work belonging to {{ $project->name }}.</p>
                </div>
                <a class="btn btn-sm btn-falcon-default" href="{{ route('planner.index', $taskBase + ['view' => 'list']) }}"><span class="fas fa-list-check me-2"></span>Manage tasks</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-hover mb-0 fs-10" data-datatables='{"pageLength":10,"language":{"emptyTable":"No tasks have been added to this project yet."}}'>
                        <thead class="bg-body-tertiary">
                            <tr><th class="ps-3">Task</th><th>Type</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Due date</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                @php
                                    $statusTone = match ($task->status) {
                                        'done' => 'success',
                                        'blocked' => 'danger',
                                        'in_progress' => 'primary',
                                        'planned' => 'info',
                                        default => 'secondary',
                                    };
                                    $priorityTone = match ($task->priority) {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'low' => 'secondary',
                                        default => 'info',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3"><a class="fw-semi-bold" href="{{ route('planner.show', $task) }}">{{ $task->title }}</a></td>
                                    <td>{{ $categories[$task->category] ?? ucfirst($task->category) }}</td>
                                    <td><span class="badge rounded-pill badge-subtle-{{ $statusTone }}">{{ $statuses[$task->status] ?? ucfirst($task->status) }}</span></td>
                                    <td><span class="badge rounded-pill badge-subtle-{{ $priorityTone }}">{{ ucfirst($task->priority) }}</span></td>
                                    <td>{{ $task->assignee->name ?? 'Unassigned' }}</td>
                                    <td>{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
