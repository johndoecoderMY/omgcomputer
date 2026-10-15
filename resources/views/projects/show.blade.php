@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Project" :title="$project->name"
        :description="$project->description"
        :back-url="route('projects.index')" back-label="All projects">
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('projects.edit', $project) }}">
                <i class="fas fa-pen me-2"></i>Edit project
            </a>
            <a class="btn btn-primary" href="{{ route('projects.tasks.create', $project) }}">
                <i class="fas fa-plus me-2"></i>Add task
            </a>
            <a class="btn btn-outline-primary" href="{{ route('customers.create', ['project_id' => $project->id]) }}">
                <i class="fas fa-user-plus me-2"></i>Add customer
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <section class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-body-secondary">Project progress</span>
                <strong>{{ $project->progress }}%</strong>
            </div>
            <div class="progress mb-2" role="progressbar" aria-label="Project progress"
                aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: {{ $project->progress }}%"></div>
            </div>
            <p class="small text-body-secondary mb-0">
                {{ $project->completed_tasks_count }} of {{ $project->tasks_count }} tasks complete
            </p>
        </div>
    </section>

    <section class="card mb-4">
        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Customers linked to this project</h2>
            <span class="badge text-bg-light border">{{ $project->customers->count() }}</span>
        </div>
        @forelse ($project->customers as $customer)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <div><a class="fw-semibold" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a><div class="small text-body-secondary">{{ $customer->email ?: $customer->phone }}</div></div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('customers.show', $customer) }}">View profile</a>
            </div>
        @empty
            <div class="card-body text-body-secondary">No customers are linked to this project yet.</div>
        @endforelse
    </section>

    <section class="card">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0">Tasks</h2>
            <span class="badge text-bg-light border">{{ $project->tasks->count() }}</span>
        </div>
        @if ($project->tasks->isNotEmpty())
            <div class="list-group list-group-flush">
                @foreach ($project->tasks as $task)
                    <article class="list-group-item">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <h3 class="h6 mb-1">
                                    <a class="text-decoration-none" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                                </h3>
                                @if ($task->description)
                                    <p class="small text-body-secondary mb-2">{{ $task->description }}</p>
                                @endif
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge task-status-{{ $task->status }}">{{ \App\Models\Task::STATUSES[$task->status] }}</span>
                                    <span class="badge text-bg-light border">{{ \App\Models\Task::PRIORITIES[$task->priority] }} priority</span>
                                    @if ($task->due_date)
                                        <span class="small text-body-secondary">Due {{ $task->due_date->format('M j, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @include('tasks._completion-toggle', ['task' => $task])
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
                                            <span class="badge task-status-{{ $subtask->status }}">{{ \App\Models\Task::STATUSES[$subtask->status] }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @include('tasks._completion-toggle', ['task' => $subtask])
                                            <a class="btn btn-sm btn-link" href="{{ route('tasks.edit', $subtask) }}">Edit</a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a class="btn btn-sm btn-link ps-0 mt-2"
                            href="{{ route('projects.tasks.create', ['project' => $project, 'parent_id' => $task->id]) }}">
                            <i class="fas fa-plus me-1"></i>Add subtask
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <div class="card-body text-center py-5">
                <p class="text-body-secondary">No tasks in this project yet.</p>
                <a class="btn btn-primary" href="{{ route('projects.tasks.create', $project) }}">Add the first task</a>
            </div>
        @endif
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <span class="small text-body-secondary">Deleting a project also removes its tasks and subtasks.</span>
            <form method="POST" action="{{ route('projects.destroy', $project) }}"
                onsubmit="return confirm('Delete this project and all its tasks?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete project</button>
            </form>
        </div>
    </section>
</div>
@endsection
