@extends('layouts.app')

@section('title', $task->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/glightbox/glightbox.min.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3" data-project-ajax-root>
    <x-page-header :eyebrow="$task->project?->name ?? 'Tasks'" :title="$task->title"
        :back-url="$task->project ? route('projects.show', $task->project) : route('tasks.index')"
        :back-label="$task->project ? 'Back to project' : 'Back to tasks'">
        <x-slot:actions>
            @if (!$task->parent_task_id && $canManageTask)
                <a class="btn btn-outline-primary" href="{{ $task->project ? route('projects.tasks.create', ['project' => $task->project, 'parent_id' => $task->id]) : route('tasks.create', ['parent_id' => $task->id]) }}" data-project-ajax-modal>
                    <i class="bi bi-plus me-2"></i>Add subtask
                </a>
            @endif
            @if ($canManageTask)
                <a class="btn btn-primary" href="{{ route('tasks.edit', $task) }}" data-project-ajax-modal>
                    <i class="bi bi-pencil me-2"></i>Edit
                </a>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                    onsubmit="return confirm('Delete this task{{ $task->subtasks->isNotEmpty() ? ' and its subtasks' : '' }}?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endif
        </x-slot:actions>
        @if ($canManageTask)
            <x-slot:actions-end>
                <form method="POST" action="{{ route('tasks.update-status', $task) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="done" value="{{ $task->status === 'done' ? '0' : '1' }}">
                    <button class="btn {{ $task->status === 'done' ? 'btn-outline-secondary' : 'btn-success' }}" type="submit">
                        <i class="bi {{ $task->status === 'done' ? 'bi-arrow-counterclockwise' : 'bi-check-lg' }} me-2" aria-hidden="true"></i>
                        {{ $task->status === 'done' ? 'Reopen task' : 'Mark as done' }}
                    </button>
                </form>
            </x-slot:actions-end>
        @endif
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
                <x-status-badge :status="$task->status" :label="\App\Models\Task::STATUSES[$task->status] ?? $task->status" />
                <span class="badge text-bg-light border">{{ \App\Models\Task::PRIORITIES[$task->priority] }} priority</span>
                @if ($task->due_date)
                    <span class="badge text-bg-light border">Due {{ $task->due_date->format('M j, Y') }}</span>
                @endif
            </div>
            <p class="mb-0 text-body-secondary">{{ $task->description ?: 'No description provided.' }}</p>
            <div class="small text-body-secondary mt-3">
                <strong>Assigned to:</strong> {{ $task->assignees->pluck('name')->join(', ') ?: 'Unassigned' }}
            </div>
            @if ($task->requirement || $task->feature || $task->milestone)
                <div class="border-top mt-3 pt-3">
                    <h2 class="h6">Planning traceability</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($task->requirement)<span class="badge text-bg-light border">{{ $task->requirement->code }} · {{ $task->requirement->requirement }}</span>@endif
                        @if ($task->feature)<span class="badge text-bg-light border">{{ $task->feature->module->name }} · {{ $task->feature->name }}</span>@endif
                        @if ($task->milestone)<span class="badge text-bg-light border">{{ $task->milestone->name }}</span>@endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-4">        <div class="card-header fw-semibold">Task photos and references</div>
        @if ($canManageTask)
        <form class="card-body border-bottom" method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data">
            @csrf
            @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            <div class="row g-3 align-items-start">
                <div class="col-md-5">
                    <label class="form-label" for="task-photos">Photos</label>
                    <x-image-upload-input id="task-photos" name="photos[]" :multiple="true" :required="true" />
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="task-photo-caption">Caption</label>
                    <input class="form-control" id="task-photo-caption" name="caption" maxlength="255" placeholder="What should the team notice?">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit">Upload photos</button>
                </div>
            </div>
            <div class="row g-2 mt-2" data-task-photo-preview aria-live="polite"></div>
            <div class="form-text">Upload up to 8 JPG, PNG, or WebP images, up to 10 MB each.</div>
        </form>
        @endif
        <div class="card-body">@include('attachments.gallery', [
            'attachments' => $task->attachments,
            'emptyMessage' => 'No task photos yet.',
            'lightbox' => true,
            'editableCaptions' => true,
        ])</div>
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
                                    <x-status-badge :status="$subtask->status" :label="\App\Models\Task::STATUSES[$subtask->status] ?? $subtask->status" />
                                    <span class="badge text-bg-light border">{{ \App\Models\Task::PRIORITIES[$subtask->priority] }} priority</span>
                                    @if ($subtask->due_date)
                                        <span class="small text-body-secondary">Due {{ $subtask->due_date->format('M j, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if (auth()->user()->hasRole('admin') || (int) $subtask->created_by_user_id === (int) auth()->id() || (int) $subtask->assigned_to === (int) auth()->id() || $subtask->assignees->contains('id', auth()->id()))
                                <div class="d-flex gap-2">
                                    @include('tasks._completion-toggle', ['task' => $subtask])
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('tasks.edit', $subtask) }}" data-project-ajax-modal>Edit</a>
                                    <form method="POST" action="{{ route('tasks.destroy', $subtask) }}"
                                        onsubmit="return confirm('Delete this subtask?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="card-body text-body-secondary">
                    No subtasks yet. Split this task into smaller steps to track progress.
                </div>
            @endif
            @if ($canManageTask)
            <div class="card-footer bg-body">
                <a class="btn btn-sm btn-outline-primary" href="{{ $task->project ? route('projects.tasks.create', ['project' => $task->project, 'parent_id' => $task->id]) : route('tasks.create', ['parent_id' => $task->id]) }}" data-project-ajax-modal>
                    <i class="bi bi-plus me-2"></i>Add subtask
                </a>
            </div>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/glightbox/glightbox.min.js') }}"></script>
    <script>
        const initializeTaskLightbox = () => {
            if (window.GLightbox) {
                window.GLightbox({ selector: '[data-gallery="task-photos"]' });
            }
        };
        initializeTaskLightbox();
        window.addEventListener('project:content-updated', initializeTaskLightbox);

        const taskPhotoInput = document.getElementById('task-photos');
        const taskPhotoPreview = document.querySelector('[data-task-photo-preview]');
        if (taskPhotoInput && taskPhotoPreview) {
            let previewUrls = [];
            taskPhotoInput.addEventListener('change', () => {
                previewUrls.forEach((url) => URL.revokeObjectURL(url));
                previewUrls = [];
                taskPhotoPreview.replaceChildren();

                Array.from(taskPhotoInput.files || []).forEach((file) => {
                    if (!file.type.startsWith('image/')) return;
                    const url = URL.createObjectURL(file);
                    previewUrls.push(url);

                    const column = document.createElement('div');
                    column.className = 'col-6 col-md-4 col-xl-3';
                    const image = document.createElement('img');
                    image.className = 'task-photo-upload-preview';
                    image.src = url;
                    image.alt = file.name;
                    image.title = file.name;
                    const name = document.createElement('div');
                    name.className = 'small text-body-secondary text-truncate mt-1';
                    name.textContent = file.name;
                    column.append(image, name);
                    taskPhotoPreview.append(column);
                });
            });
            window.addEventListener('pagehide', () => {
                previewUrls.forEach((url) => URL.revokeObjectURL(url));
            }, { once: true });
        }
    </script>
    <style>
        .task-photo-upload-preview{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;border:1px solid var(--falcon-border-color);border-radius:4px;background:var(--falcon-tertiary-bg)}
    </style>
@endpush
