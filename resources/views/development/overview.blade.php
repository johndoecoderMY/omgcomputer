@extends('layouts.app')

@section('title', 'Development Overview')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" 
        title="Overview"
    
  >
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('development.flowcharts.index') }}">
                <span class="fas fa-diagram-project me-2"></span>Manage flow charts
            </a>
            <a class="btn btn-outline-primary" href="{{ route('projects.index') }}">
                <span class="fas fa-folder-open me-2"></span>Projects
            </a>
            <a class="btn btn-primary" href="{{ route('tasks.index') }}">
                <span class="fas fa-list-check me-2"></span>Manage tasks
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info d-flex align-items-start gap-2" role="alert">
        <span class="fas fa-info-circle mt-1"></span>
        <div>
            <strong>Preview mode</strong>
            <div class="small">Track website development tasks and see how each part of the site fits together.</div>
        </div>
    </div>

    <section class="card mb-4 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center
            rounded-circle bg-primary text-white"
     style="width: 128px; height: 128px; ">
    <i class="fas fa-desktop fs-2"></i>
</div>
                        <div>
                            <span class="badge badge-subtle-primary mb-1">Main project</span>
                            <h2 class="h3 mb-0">OMG Computer Website</h2>
                        </div>
                    </div>
                    <p class="text-700 mb-0">Plan and track website development, computer repair services, support
                        tickets, shop operations, and student consultation services in one place.</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge badge-subtle-primary">Website development</span>
                        <span class="badge badge-subtle-info">Repair services</span>
                        <span class="badge badge-subtle-success">Student services</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-600 text-uppercase fs-10 fw-bold">Overall completion</span>
                        <strong>{{ $taskCounts['percent'] }}%</strong>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Project completion" aria-valuenow="{{ $taskCounts['percent'] }}"
                        aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: {{ $taskCounts['percent'] }}%"></div>
                    </div>
                    <p class="text-600 fs-10 mt-2 mb-0">{{ $taskCounts['done'] }} of {{ $taskCounts['total'] }} tasks completed</p>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <a class="card h-100 text-decoration-none" href="{{ route('tasks.index') }}">
                <div class="card-body">
                    <p class="text-600 text-uppercase fs-10 fw-bold mb-2">All tasks</p>
                    <p class="h3 mb-1">{{ $taskCounts['total'] }}</p>
                    <span class="text-600 fs-10">{{ $taskCounts['remaining'] }} still to do</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl">
            <a class="card h-100 text-decoration-none" href="{{ route('tasks.index', ['status' => 'done']) }}">
                <div class="card-body">
                    <p class="text-success text-uppercase fs-10 fw-bold mb-2">Done</p>
                    <p class="h3 text-success mb-1">{{ $taskCounts['done'] }}</p>
                    <span class="text-600 fs-10">Completed tasks</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl">
            <a class="card h-100 text-decoration-none" href="{{ route('tasks.index', ['status' => 'in_progress']) }}">
                <div class="card-body">
                    <p class="text-primary text-uppercase fs-10 fw-bold mb-2">In progress</p>
                    <p class="h3 text-primary mb-1">{{ $taskCounts['inProgress'] }}</p>
                    <span class="text-600 fs-10">Being worked on now</span>
                </div>
            </a>
        </div>
    </div>

    <section class="card mb-4">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <h2 class="h5 mb-1">Website flow charts</h2>
                <p class="small text-body-secondary mb-0">Map the pages and actions that make up the website.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('development.flowcharts.create') }}">
                <i class="fas fa-plus me-2"></i>New flow chart
            </a>
        </div>
        <div class="card-body">
            @forelse ($flowCharts as $flowChart)
                <a class="flow-chart-preview d-block text-decoration-none p-3 rounded-2 {{ $loop->last ? '' : 'mb-3' }}"
                    href="{{ route('development.flowcharts.show', $flowChart) }}">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $flowChart->title }}</h3>
                            @if ($flowChart->description)
                                <p class="small text-body-secondary mb-0">{{ $flowChart->description }}</p>
                            @endif
                        </div>
                        <span class="badge text-bg-light border">{{ count($flowChart->steps) }} steps</span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small text-body-secondary">
                            {{ collect($flowChart->steps)->sum(fn ($step) => count($step['connections'] ?? [])) }} connections
                        </span>
                    </div>
                </a>
            @empty
                <div class="text-center py-4">
                    <p class="text-body-secondary mb-3">No flow charts yet. Create one to map the website journey.</p>
                    <a class="btn btn-primary" href="{{ route('development.flowcharts.create') }}">
                        <i class="fas fa-plus me-2"></i>Create a flow chart
                    </a>
                </div>
            @endforelse
            @if ($flowCharts->isNotEmpty())
                <a class="btn btn-link px-0 pb-0" href="{{ route('development.flowcharts.index') }}">
                    View and manage all flow charts <i class="fas fa-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </section>
    </div>
@endsection