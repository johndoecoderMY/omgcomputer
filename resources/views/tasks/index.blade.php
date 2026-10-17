@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Projects" title="Tasks"
        description="Review project tasks and subtasks in one place.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('projects.index') }}">
                <i class="fas fa-folder-open me-2"></i>Choose a project
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <form class="card card-body mb-3 p-2" method="GET" action="{{ route('tasks.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label" for="search">Search tasks</label>
                <input class="form-control" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Search task titles and descriptions">
            </div>
            <div class="col-sm-5 col-lg-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-2">
                <label class="form-label" for="priority">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2 col-lg-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('tasks.index') }}">Reset</a>
            </div>
        </div>
    </form>

    <div class="d-grid gap-3">
        @forelse ($tasks as $task)
            <article class="card p-2">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h5 mb-1">
                                <a class="text-decoration-none" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                            </h2>
                            @if ($task->description)
                                <p class="text-body-secondary mb-2">{{ $task->description }}</p>
                            @endif
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge task-status-{{ $task->status }}">{{ $statuses[$task->status] }}</span>
                                <span class="badge text-bg-light border">{{ $priorities[$task->priority] }} priority</span>
                                <a class="small text-decoration-none" href="{{ route('projects.show', $task->project) }}">{{ $task->project->name }}</a>
                                @if ($task->due_date)
                                    <span class="small text-body-secondary">Due {{ $task->due_date->format('M j, Y') }}</span>
                                @endif
                                <span class="small text-body-secondary">{{ $task->subtasks->count() }} subtasks</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            @include('tasks._completion-toggle', ['task' => $task])
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('tasks.show', $task) }}">Details</a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('tasks.edit', $task) }}">Edit</a>
                        </div>
                    </div>
                    @if ($task->subtasks->isNotEmpty())
                        <ul class="task-subtask-list list-unstyled mt-3 mb-0">
                            @foreach ($task->subtasks as $subtask)
                                <li class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-turn-down text-body-tertiary" aria-hidden="true"></i>
                                        <a class="text-decoration-none" href="{{ route('tasks.show', $subtask) }}">{{ $subtask->title }}</a>
                                        <span class="badge task-status-{{ $subtask->status }}">{{ $statuses[$subtask->status] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('tasks._completion-toggle', ['task' => $subtask])
                                        <a class="btn btn-sm btn-link" href="{{ route('tasks.edit', $subtask) }}">Edit</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>
        @empty
            <div class="card p-2">
                <div class="card-body text-center py-5">
                    <i class="fas fa-list-check text-primary fs-2 mb-3" aria-hidden="true"></i>
                    <h2 class="h5">No tasks found</h2>
                    <p class="text-body-secondary">Create a project first, then add tasks and subtasks inside it.</p>
                    <a class="btn btn-primary" href="{{ route('projects.create') }}">Create a project</a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
