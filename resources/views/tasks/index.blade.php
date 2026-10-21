@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Projects" title="Tasks"
        description="Review project tasks and subtasks in one place.">
        <x-slot:actions>
            <div class="btn-group" role="group" aria-label="Task view">
                <button class="btn btn-outline-secondary active" type="button" data-task-view="list" aria-pressed="true">
                    <i class="fas fa-list me-1"></i>List
                </button>
                <button class="btn btn-outline-secondary" type="button" data-task-view="kanban" aria-pressed="false">
                    <i class="fas fa-columns me-1"></i>Kanban
                </button>
            </div>
            <a class="btn btn-primary" href="{{ route('tasks.create') }}">
                <i class="fas fa-plus me-2"></i>Add task
            </a>
        </x-slot:actions>
    </x-page-header>

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
            data-source="{{ route('tasks.datatable') }}"
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
        <div class="row g-3" data-task-kanban data-source="{{ route('tasks.kanban') }}"
            data-token="{{ csrf_token() }}">
            @foreach ($statuses as $status => $label)
                <div class="col-12 col-lg-4">
                    <section class="card h-100 task-kanban-column" data-task-column="{{ $status }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">{{ $label }}</h2>
                            <span class="badge text-bg-secondary" data-task-count>0</span>
                        </div>
                        <div class="card-body d-flex flex-column gap-2 task-kanban-dropzone"
                            data-task-dropzone="{{ $status }}" aria-label="{{ $label }} tasks">
                            <span class="small text-body-secondary" data-task-empty>No tasks</span>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
        <p class="small text-body-secondary mt-2 mb-0">The board shows all top-level tasks. Drag a task to another column to update its status.</p>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .task-kanban-column { min-height: 12rem; }
    .task-kanban-dropzone { min-height: 8rem; }
    .task-kanban-dropzone.is-drop-target { background: var(--falcon-primary-bg-subtle, #e7f1ff); }
    .task-kanban-card { cursor: grab; }
    .task-kanban-card[aria-disabled="true"] { cursor: default; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const board = document.querySelector('[data-task-kanban]');
        const listView = document.querySelector('[data-task-list-view]');
        const boardView = document.querySelector('[data-task-kanban-view]');
        const filters = document.querySelector('[data-task-filters]');
        const errorBox = document.querySelector('[data-task-kanban-error]');
        if (!board || !listView || !boardView) return;

        let loaded = false;
        const statuses = ['backlog', 'in_progress', 'done'];

        const setError = (message = '') => {
            errorBox.textContent = message;
            errorBox.classList.toggle('d-none', !message);
        };

        const renderTasks = (tasks) => {
            const zones = new Map(statuses.map((status) => [
                status,
                board.querySelector(`[data-task-dropzone="${status}"]`),
            ]));
            zones.forEach((zone) => zone.replaceChildren());
            const counts = new Map(statuses.map((status) => [status, 0]));

            tasks.forEach((task) => {
                const zone = zones.get(task.status);
                if (!zone) return;
                counts.set(task.status, counts.get(task.status) + 1);

                const card = document.createElement('article');
                card.className = 'card task-kanban-card';
                card.dataset.taskId = task.id;
                card.dataset.statusUrl = task.status_url;
                card.dataset.status = task.status;
                card.draggable = task.can_update;
                card.setAttribute('aria-disabled', String(!task.can_update));

                const body = document.createElement('div');
                body.className = 'card-body p-3';
                const title = document.createElement('a');
                title.className = 'fw-semibold d-block mb-1';
                title.href = task.url;
                title.textContent = task.title;
                const details = document.createElement('p');
                details.className = 'small text-body-secondary mb-2';
                details.textContent = task.description || 'No description';
                const meta = document.createElement('div');
                meta.className = 'd-flex flex-wrap gap-1';
                [task.priority, task.project, task.due_date, task.assignees.join(', ')].filter(Boolean).forEach((text) => {
                    const badge = document.createElement('span');
                    badge.className = 'badge text-bg-light border';
                    badge.textContent = text;
                    meta.append(badge);
                });
                body.append(title, details, meta);
                card.append(body);
                zone.append(card);
            });

            statuses.forEach((status) => {
                const zone = zones.get(status);
                const count = counts.get(status);
                board.querySelector(`[data-task-column="${status}"] [data-task-count]`).textContent = count;
                if (!count) {
                    const empty = document.createElement('span');
                    empty.className = 'small text-body-secondary';
                    empty.dataset.taskEmpty = '';
                    empty.textContent = 'No tasks';
                    zone.append(empty);
                }
            });
        };

        const loadTasks = async () => {
            setError();
            try {
                const response = await fetch(board.dataset.source, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('Could not load tasks. Please try again.');
                renderTasks(await response.json());
                loaded = true;
            } catch (error) {
                setError(error.message || 'Could not load tasks. Please try again.');
            }
        };

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
                if (kanban && !loaded) loadTasks();
            });
        });

        board.addEventListener('dragstart', (event) => {
            const card = event.target.closest('[data-task-id]');
            if (!card || !card.draggable) return;
            event.dataTransfer.setData('text/plain', card.dataset.taskId);
            event.dataTransfer.effectAllowed = 'move';
        });

        board.addEventListener('dragover', (event) => {
            const zone = event.target.closest('[data-task-dropzone]');
            if (!zone) return;
            event.preventDefault();
            zone.classList.add('is-drop-target');
        });

        board.addEventListener('dragleave', (event) => {
            const zone = event.target.closest('[data-task-dropzone]');
            if (zone && !zone.contains(event.relatedTarget)) zone.classList.remove('is-drop-target');
        });

        board.addEventListener('drop', async (event) => {
            const zone = event.target.closest('[data-task-dropzone]');
            if (!zone) return;
            event.preventDefault();
            zone.classList.remove('is-drop-target');
            const taskId = event.dataTransfer.getData('text/plain');
            const card = board.querySelector(`[data-task-id="${taskId}"]`);
            const nextStatus = zone.dataset.taskDropzone;
            if (!card || card.dataset.status === nextStatus) return;

            const previousStatus = card.dataset.status;
            card.classList.add('opacity-50');
            setError();
            try {
                const response = await fetch(card.dataset.statusUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    },
                    body: new URLSearchParams({
                        _token: board.dataset.token,
                        status: nextStatus,
                    }),
                });
                if (!response.ok) throw new Error('Could not update the task status.');
                const updated = await response.json();
                card.dataset.status = updated.status;
                zonesMoveCard(card, zone, previousStatus);
            } catch (error) {
                card.classList.remove('opacity-50');
                setError(error.message || 'Could not update the task status.');
            }
        });

        const zonesMoveCard = (card, targetZone, previousStatus) => {
            const previousZone = board.querySelector(`[data-task-dropzone="${previousStatus}"]`);
            previousZone.querySelector('[data-task-empty]')?.remove();
            targetZone.querySelector('[data-task-empty]')?.remove();
            targetZone.append(card);
            card.classList.remove('opacity-50');

            [previousZone, targetZone].forEach((zone) => {
                const cards = zone.querySelectorAll('[data-task-id]');
                const column = zone.closest('[data-task-column]');
                column.querySelector('[data-task-count]').textContent = cards.length;
                if (!cards.length) {
                    const empty = document.createElement('span');
                    empty.className = 'small text-body-secondary';
                    empty.dataset.taskEmpty = '';
                    empty.textContent = 'No tasks';
                    zone.append(empty);
                }
            });
        };
    });
</script>
@endpush
