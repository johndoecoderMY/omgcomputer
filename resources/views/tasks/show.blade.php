@extends('layouts.app')

@section('title', $task->title)

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$task->project->name" :title="$task->title"
        :back-url="route('projects.show', $task->project)" back-label="Back to project">
        <x-slot:actions>
            @if (!$task->parent_task_id)
                <a class="btn btn-outline-primary" href="{{ route('projects.tasks.create', ['project' => $task->project, 'parent_id' => $task->id]) }}">
                    <i class="fas fa-plus me-2"></i>Add subtask
                </a>
            @endif
            <a class="btn btn-primary" href="{{ route('tasks.edit', $task) }}">
                <i class="fas fa-pen me-2"></i>Edit
            </a>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                onsubmit="return confirm('Delete this task{{ $task->subtasks->isNotEmpty() ? ' and its subtasks' : '' }}?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">Delete</button>
            </form>
            @include('tasks._completion-toggle', ['task' => $task])
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($task->parent)
        <div class="alert alert-info">
            Subtask of <a href="{{ route('tasks.show', $task->parent) }}">{{ $task->parent->title }}</a>
        </div>
    @endif

    <div class="card mb-4 p-2">        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge task-status-{{ $task->status }}">{{ \App\Models\Task::STATUSES[$task->status] }}</span>
                <span class="badge text-bg-light border">{{ \App\Models\Task::PRIORITIES[$task->priority] }} priority</span>
                @if ($task->due_date)
                    <span class="badge text-bg-light border">Due {{ $task->due_date->format('M j, Y') }}</span>
                @endif
            </div>
            <p class="mb-0 text-body-secondary">{{ $task->description ?: 'No description provided.' }}</p>
        </div>
    </div>

    <div class="card mb-4">        <div class="card-header fw-semibold">Task photos and references</div>
        <form class="card-body border-bottom" method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data">
            @csrf
            @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            <div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="task-photos">Photos</label><input class="form-control" id="task-photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required></div><div class="col-md-5"><label class="form-label" for="task-photo-caption">Caption</label><input class="form-control" id="task-photo-caption" name="caption" maxlength="255" placeholder="What should the team notice?"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Upload photos</button></div></div>
            <div class="form-text">Upload up to 8 JPG, PNG, or WebP images, up to 10 MB each.</div>
        </form>
        <div class="card-body">@include('attachments.gallery', ['attachments' => $task->attachments, 'emptyMessage' => 'No task photos yet.'])</div>
    </div>

    @if (!$task->parent_task_id)
        <div class="card">            <div class="card-header bg-body d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Subtasks</h2>
                <span class="badge text-bg-light border">{{ $task->subtasks->count() }}</span>
            </div>
            @if ($task->subtasks->isNotEmpty())
                <ul class="list-group list-group-flush">
                    @foreach ($task->subtasks as $subtask)
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <a class="fw-semibold text-decoration-none" href="{{ route('tasks.show', $subtask) }}">{{ $subtask->title }}</a>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <span class="badge task-status-{{ $subtask->status }}">{{ \App\Models\Task::STATUSES[$subtask->status] }}</span>
                                    <span class="badge text-bg-light border">{{ \App\Models\Task::PRIORITIES[$subtask->priority] }} priority</span>
                                    @if ($subtask->due_date)
                                        <span class="small text-body-secondary">Due {{ $subtask->due_date->format('M j, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                @include('tasks._completion-toggle', ['task' => $subtask])
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('tasks.edit', $subtask) }}">Edit</a>
                                <form method="POST" action="{{ route('tasks.destroy', $subtask) }}"
                                    onsubmit="return confirm('Delete this subtask?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="card-body text-body-secondary">
                    No subtasks yet. Split this task into smaller steps to track progress.
                </div>
            @endif
            <div class="card-footer bg-body">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.tasks.create', ['project' => $task->project, 'parent_id' => $task->id]) }}">
                    <i class="fas fa-plus me-2"></i>Add subtask
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
