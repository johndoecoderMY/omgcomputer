@extends('layouts.app')

@section('title', $project ? $project->name.' Tasks' : 'My Tasks')

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$project?->name ?? 'System'" :title="$project ? 'Tasks' : 'My Tasks'"
        :description="$project ? 'Review tasks and subtasks for '.$project->name.'.' : 'Review tasks and subtasks across all projects.'"
        :back-url="$project ? route('projects.show', $project) : null"
        back-label="Back to project">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ $project ? route('projects.tasks.create', $project) : route('tasks.create') }}">
                <i class="bi bi-plus me-2"></i>Add task
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="d-flex justify-content-end mb-3">
        <div class="btn-group" role="group" aria-label="Task view">
            <button class="btn btn-outline-secondary active" type="button" data-task-view="list" aria-pressed="true">
                <i class="bi bi-list me-1"></i>List
            </button>
            <button class="btn btn-outline-secondary" type="button" data-task-view="kanban" aria-pressed="false">
                <i class="bi bi-layout-three-columns me-1"></i>Kanban
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card card-body mb-3 p-2" data-task-filters>
        <div class="row g-2 align-items-end">
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="task-status">Status</label>
                <select class="form-select" id="task-status" data-datatable-filter>
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="task-priority">Priority</label>
                <select class="form-select" id="task-priority" data-datatable-filter>
                    <option value="">All priorities</option>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="task-search">Search tasks</label>
                <input class="form-control" id="task-search" type="search" data-datatable-search
                    placeholder="Search tasks, projects, or assignees">
            </div>
        </div>
    </div>

    <div class="card p-2" data-task-list-view><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ $project ? route('projects.tasks.datatable', $project) : route('tasks.datatable') }}"
            data-datatable-filters="{{ json_encode([
                ['name' => 'status', 'selector' => '#task-status'],
                ['name' => 'priority', 'selector' => '#task-priority'],
            ]) }}"
            data-datatable-search="#task-search"
            data-columns="{{ json_encode([
                ['data' => 'task', 'name' => 'tasks.title'],
                ['data' => 'project_name', 'orderable' => false, 'searchable' => false],
                ['data' => 'assignees', 'orderable' => false, 'searchable' => false],
                ['data' => 'status_label', 'name' => 'tasks.status'],
                ['data' => 'priority_label', 'name' => 'tasks.priority'],
                ['data' => 'due_date', 'name' => 'tasks.due_date'],
                ['data' => 'subtasks_count', 'name' => 'subtasks_count'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Task</th><th>Project</th><th>Assigned to</th><th>Status</th><th>Priority</th><th>Due</th><th>Subtasks</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
    </div>
    <div class="d-none" data-task-kanban-view>
        <div class="alert alert-danger d-none" role="alert" data-task-kanban-error></div>
        <div class="row gx-0 kanban-header rounded-2 px-x1 py-2 mb-3 align-items-center">
            <div class="col d-flex align-items-center">
                <h2 class="h5 mb-0">Task board</h2>
                <div class="vertical-line vertical-line-400 position-relative h-100 mx-3"></div>
                <span class="small text-body-secondary"><span data-task-total>{{ count($boardTasks) }}</span> tasks</span>
            </div>
            <div class="col-auto">
                <a class="btn btn-sm btn-falcon-default" href="{{ $project ? route('projects.tasks.create', $project) : route('tasks.create') }}">
                    <span class="bi bi-plus me-2" aria-hidden="true"></span>Add task
                </a>
            </div>
        </div>
        <div class="kanban-container scrollbar me-n3" data-task-kanban
            data-token="{{ csrf_token() }}" aria-label="Task board">
            @foreach ($statuses as $status => $label)
                @php
                    $columnTasks = collect($boardTasks)->where('status', $status);
                    $priorityClasses = [
                        'Urgent' => 'badge-subtle-danger',
                        'High' => 'badge-subtle-warning',
                        'Medium' => 'badge-subtle-info',
                        'Low' => 'badge-subtle-secondary',
                    ];
                @endphp
                <section class="kanban-column task-kanban-column" data-task-column="{{ $status }}"
                    aria-label="{{ $label }}">
                    <div class="kanban-column-header">
                        <h3 class="fs-9 mb-0">{{ $label }} <span class="text-500">(<span data-task-count>{{ $columnTasks->count() }}</span>)</span></h3>
                        <span class="bi {{ $status === 'done' ? 'bi-check-circle text-success' : ($status === 'in_progress' ? 'bi-arrow-repeat text-primary' : 'bi-inbox text-secondary') }}"
                            aria-hidden="true"></span>
                    </div>
                    <div class="kanban-items-container scrollbar task-kanban-dropzone"
                        data-sortable='{"draggable":".sortable-item-wrapper:not([aria-disabled=true])"}'
                        data-task-dropzone="{{ $status }}" aria-label="{{ $label }} tasks">
                        @forelse ($columnTasks as $task)
                            <div class="kanban-item sortable-item-wrapper task-kanban-card" data-task-id="{{ $task['id'] }}"
                                data-status-url="{{ $task['status_url'] }}" data-status="{{ $task['status'] }}"
                                aria-disabled="{{ $task['can_update'] ? 'false' : 'true' }}">
                                <div class="card sortable-item kanban-item-card hover-actions-trigger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div class="d-flex align-items-center gap-2 text-truncate">
                                                @if ($task['project'])
                                                    <span class="badge badge-subtle-primary text-truncate">{{ $task['project'] }}</span>
                                                @else
                                                    <span class="small text-body-secondary">General task</span>
                                                @endif
                                                <span class="badge {{ $priorityClasses[$task['priority']] ?? 'badge-subtle-secondary' }} flex-shrink-0">
                                                    {{ $task['priority'] }}
                                                </span>
                                            </div>
                                            <button class="btn btn-sm btn-falcon-default task-kanban-drag-handle flex-shrink-0"
                                                type="button" aria-label="Touch and hold to drag task">
                                                <span class="bi bi-grip-vertical" aria-hidden="true"></span>
                                            </button>
                                        </div>
                                        <button class="btn btn-link p-0 fw-semibold text-900 text-start d-block mb-2"
                                            type="button" data-bs-toggle="modal" data-bs-target="#task-details-modal"
                                            data-task-title="{{ $task['title'] }}"
                                            data-task-description="{{ $task['description'] ?: 'No description' }}"
                                            data-task-status="{{ $task['status_label'] }}"
                                            data-task-priority="{{ $task['priority'] }}"
                                            data-task-project="{{ $task['project'] ?: 'General task' }}"
                                            data-task-parent="{{ $task['parent'] ?: 'No parent task' }}"
                                            data-task-due-date="{{ $task['due_date'] ?: 'No due date' }}"
                                            data-task-assignees="{{ $task['assignees']->join(', ') ?: 'Unassigned' }}"
                                            data-task-url="{{ $task['url'] }}">
                                            {{ $task['title'] }}
                                        </button>
                                        @if ($task['parent'])
                                            <p class="small text-body-secondary mb-2">
                                                <span class="bi bi-arrow-right bi-rotate-90 me-1" aria-hidden="true"></span>
                                                Subtask of {{ $task['parent'] }}
                                            </p>
                                        @endif
                                        <p class="small text-body-secondary mb-0">
                                            {{ $task['description'] ?: 'No description' }}
                                        </p>
                                        <div class="kanban-item-footer">
                                            <span class="d-inline-flex align-items-center">
                                                @if ($task['due_date'])
                                                    <span class="bi bi-calendar me-1" aria-hidden="true"></span>{{ $task['due_date'] }}
                                                @else
                                                    No due date
                                                @endif
                                            </span>
                                            <span class="d-inline-flex align-items-center text-truncate ms-2"
                                                title="{{ $task['assignees']->join(', ') ?: 'Unassigned' }}">
                                                <span class="bi bi-person me-1" aria-hidden="true"></span>
                                                {{ $task['assignees']->first() ?: 'Unassigned' }}
                                                @if ($task['assignees']->count() > 1)
                                                    +{{ $task['assignees']->count() - 1 }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="task-kanban-empty small text-body-secondary text-center py-4"
                                data-task-empty>Nothing here yet</div>
                        @endforelse
                    </div>
                    <div class="kanban-column-footer small text-body-secondary">
                        <span data-task-column-summary>{{ $columnTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $columnTasks->count()) }}</span>
                    </div>
                </section>
            @endforeach
        </div>
        <p class="small text-body-secondary mt-2 mb-0">
            Drag cards between columns to update status. On touch screens, press and hold the grip, then drag.
            Select a task title to view its details.
        </p>
    </div>
    <div class="modal fade" id="task-details-modal" tabindex="-1" aria-labelledby="task-details-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="text-body-secondary small">Task details</span>
                        <h2 class="modal-title fs-5 mb-0" id="task-details-title" data-task-modal-title></h2>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" data-task-modal-description></p>
                    <dl class="row mb-0">
                        <dt class="col-4">Status</dt>
                        <dd class="col-8" data-task-modal-status></dd>
                        <dt class="col-4">Priority</dt>
                        <dd class="col-8" data-task-modal-priority></dd>
                        <dt class="col-4">Project</dt>
                        <dd class="col-8" data-task-modal-project></dd>
                        <dt class="col-4">Parent task</dt>
                        <dd class="col-8" data-task-modal-parent></dd>
                        <dt class="col-4">Due date</dt>
                        <dd class="col-8" data-task-modal-due-date></dd>
                        <dt class="col-4">Assigned to</dt>
                        <dd class="col-8" data-task-modal-assignees></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    <a class="btn btn-primary" href="#" data-task-modal-open>Open task</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .task-kanban-column { min-height: 16rem; }
    .task-kanban-card[aria-disabled="true"] { cursor: default; }
    .task-kanban-column.is-saving { opacity: .7; }
    .task-kanban-drag-handle { cursor: grab; padding: .2rem .45rem; touch-action: none; }
    .task-kanban-drag-handle:active { cursor: grabbing; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const board = document.querySelector('[data-task-kanban]');
        const listView = document.querySelector('[data-task-list-view]');
        const boardView = document.querySelector('[data-task-kanban-view]');
        const filters = document.querySelector('[data-task-filters]');
        const taskModal = document.querySelector('#task-details-modal');
        if (!board || !listView || !boardView) return;
        const listTable = document.querySelector('table[data-datatable]');
        const refreshTaskList = () => {
            if (!window.jQuery || !listTable || !window.jQuery.fn.DataTable.isDataTable(listTable)) return;
            window.jQuery(listTable).DataTable().ajax.reload(null, false);
        };
        let errorBox = boardView.querySelector('[data-task-kanban-error]');
        if (!errorBox) {
            errorBox = document.createElement('div');
            errorBox.className = 'alert alert-danger d-none';
            errorBox.setAttribute('role', 'alert');
            errorBox.dataset.taskKanbanError = '';
            boardView.prepend(errorBox);
        }
        if (!window.Sortable) {
            errorBox.textContent = 'Task board drag-and-drop could not be initialized.';
            errorBox.classList.remove('d-none');
            return;
        }

        const setError = (message = '') => {
            errorBox.textContent = message;
            errorBox.classList.toggle('d-none', !message);
        };

        taskModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger?.matches('[data-task-title]')) return;

            const fields = [
                ['title', trigger.dataset.taskTitle],
                ['description', trigger.dataset.taskDescription],
                ['status', trigger.dataset.taskStatus],
                ['priority', trigger.dataset.taskPriority],
                ['project', trigger.dataset.taskProject],
                ['parent', trigger.dataset.taskParent],
                ['due-date', trigger.dataset.taskDueDate],
                ['assignees', trigger.dataset.taskAssignees],
            ];
            fields.forEach(([field, value]) => {
                taskModal.querySelector(`[data-task-modal-${field}]`).textContent = value;
            });
            taskModal.querySelector('[data-task-modal-open]').href = trigger.dataset.taskUrl;
        });

        const updateColumnCounts = () => {
            board.querySelectorAll('[data-task-column]').forEach((column) => {
                const zone = column.querySelector('[data-task-dropzone]');
                const cards = zone.querySelectorAll('.task-kanban-card');
                const count = cards.length;
                column.querySelector('[data-task-count]').textContent = count;
                column.querySelector('[data-task-column-summary]').textContent =
                    `${count} ${count === 1 ? 'task' : 'tasks'}`;
                const empty = zone.querySelector('[data-task-empty]');
                if (count === 0 && !empty) {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'task-kanban-empty small text-body-secondary text-center py-4';
                    placeholder.dataset.taskEmpty = '';
                    placeholder.textContent = 'Nothing here yet';
                    zone.append(placeholder);
                } else if (count > 0) {
                    empty?.remove();
                }
            });
        };

        const originalPositions = new WeakMap();
        board.querySelectorAll('[data-task-dropzone]').forEach((zone) => {
            const sortable = window.Sortable.get(zone) || window.Sortable.create(zone, {
                animation: 150,
                group: { name: 'task-board' },
                delay: 100,
                delayOnTouchOnly: true,
                forceFallback: true,
                draggable: '.sortable-item-wrapper:not([aria-disabled="true"])',
            });
            if (!sortable) {
                setError('Task board drag-and-drop could not be initialized.');
                return;
            }

            sortable.option('onStart', (event) => {
                originalPositions.set(event.item, {
                    status: event.item.dataset.status,
                    nextSibling: event.item.nextElementSibling,
                });
                document.body.classList.add('sortable-dragging');
            });
            sortable.option('onEnd', async (event) => {
                document.body.classList.remove('sortable-dragging');
                const card = event.item;
                const sourceZone = event.from;
                const targetZone = event.to;
                const originalPosition = originalPositions.get(card);
                originalPositions.delete(card);
                const previousStatus = originalPosition?.status || card.dataset.status;
                const nextStatus = targetZone.dataset.taskDropzone;
                if (previousStatus === nextStatus) return;

                card.dataset.status = nextStatus;
                sourceZone.closest('[data-task-column]').classList.add('is-saving');
                targetZone.closest('[data-task-column]').classList.add('is-saving');
                setError();
                updateColumnCounts();

                try {
                    const response = await fetch(card.dataset.statusUrl, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-CSRF-TOKEN': board.dataset.token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new URLSearchParams({ status: nextStatus }),
                    });
                    if (!response.ok) {
                        const payload = await response.json().catch(() => null);
                        const message = payload?.message || (response.status === 403
                            ? 'You do not have permission to update this task.'
                            : `Could not save the task status (HTTP ${response.status}).`);
                        throw new Error(message);
                    }
                    const updated = await response.json();
                    if (updated.status !== nextStatus) {
                        throw new Error('The server returned a different task status.');
                    }
                    refreshTaskList();
                } catch (error) {
                    const nextSibling = originalPosition?.nextSibling;
                    sourceZone.insertBefore(
                        card,
                        nextSibling?.parentElement === sourceZone ? nextSibling : null,
                    );
                    card.dataset.status = previousStatus;
                    setError(error.message || 'Could not save the task status.');
                } finally {
                    sourceZone.closest('[data-task-column]').classList.remove('is-saving');
                    targetZone.closest('[data-task-column]').classList.remove('is-saving');
                    updateColumnCounts();
                }
            });
            sortable.option('filter', 'a, button:not(.task-kanban-drag-handle)');
            sortable.option('preventOnFilter', false);
            sortable.option('handle', '.task-kanban-drag-handle');
            sortable.option('delay', 150);
            sortable.option('delayOnTouchOnly', true);
            sortable.option('touchStartThreshold', 5);
            sortable.option('fallbackTolerance', 4);
            sortable.option('forceFallback', true);
            sortable.option('scroll', true);
            sortable.option('bubbleScroll', true);
        });
        updateColumnCounts();

        document.querySelectorAll('[data-task-view]').forEach((button) => {
            button.addEventListener('click', () => {
                const kanban = button.dataset.taskView === 'kanban';
                listView.classList.toggle('d-none', kanban);
                boardView.classList.toggle('d-none', !kanban);
                filters.classList.toggle('d-none', kanban);
                document.querySelectorAll('[data-task-view]').forEach((viewButton) => {
                    const active = viewButton === button;
                    viewButton.classList.toggle('active', active);
                    viewButton.setAttribute('aria-pressed', String(active));
                });
            });
        });

    });
</script>
@endpush
