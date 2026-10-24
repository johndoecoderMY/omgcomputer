@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="My project" :title="$project->name"
        :description="$project->description"
        :back-url="route('projects.index')" back-label="My projects" />

    <div class="card mb-4" aria-labelledby="customer-project-requirements-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-requirements-heading">Project requirements</h2>
        </div>
        <div class="card-body">
            @if ($project->requirements_notes)
                <p class="mb-3" style="white-space: pre-line">{{ $project->requirements_notes }}</p>
            @endif
            <h3 class="h6">Checklist</h3>
            @forelse ($project->requirements as $requirement)
                <div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <i class="bi {{ $requirement->is_completed ? 'bi-check-square text-success' : 'bi-square text-body-secondary' }} mt-1" aria-hidden="true"></i>
                    <span @class(['text-decoration-line-through text-body-secondary' => $requirement->is_completed])>{{ $requirement->requirement }}</span>
                    <span class="visually-hidden">{{ $requirement->is_completed ? 'Complete' : 'Incomplete' }}</span>
                </div>
            @empty
                @unless ($project->requirements_notes)
                    <p class="text-body-secondary mb-0">No project requirements have been added yet.</p>
                @endunless
            @endforelse
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-schedule-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-schedule-heading">Project schedule</h2>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->events as $event)
                <article class="list-group-item px-3 py-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <h3 class="h6 mb-0">{{ $event->title }}</h3>
                        <span class="badge text-bg-light border">{{ \App\Models\Event::TYPES[$event->event_type] ?? 'Event' }}</span>
                    </div>
                    <p class="small text-body-secondary mt-2 mb-1">
                        {{ $event->starts_at->format('D, M j, Y · g:i A') }}
                        @if ($event->ends_at) – {{ $event->ends_at->format('g:i A') }}@endif
                    </p>
                    @if ($event->location)<p class="small text-body-secondary mb-1">{{ $event->location }}</p>@endif
                    @if ($event->description)<p class="mb-0">{{ $event->description }}</p>@endif
                </article>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No scheduled project events yet.</p>
            @endforelse
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-flowcharts-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-flowcharts-heading">Flow charts &amp; SOPs</h2>
        </div>
        <div class="card-body">
            @forelse ($project->flowCharts as $flowChart)
                @php
                    $flowSteps = collect($flowChart->steps ?? []);
                    $stepsById = $flowSteps->keyBy('id');
                @endphp
                <article @class(['pb-3', 'mb-3 border-bottom' => !$loop->last])>
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div>
                            <h3 class="h6 mb-1">{{ $flowChart->title }}</h3>
                            @if ($flowChart->subject_name)
                                <p class="small text-body-secondary mb-0">{{ $flowChart->subject_name }}</p>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge text-bg-light border">{{ $flowChart->categoryLabel() }}</span>
                            <button class="btn btn-sm btn-outline-primary" type="button"
                                data-bs-toggle="modal" data-bs-target="#customer-flowchart-{{ $flowChart->id }}">
                                <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>View flow chart
                            </button>
                        </div>
                    </div>
                    @if ($flowChart->description)
                        <p class="small mt-2 mb-2">{{ $flowChart->description }}</p>
                    @endif
                    @if ($flowSteps->isNotEmpty())
                        <ol class="list-group list-group-numbered mt-3">
                            @foreach ($flowSteps as $step)
                                <li class="list-group-item">
                                    <span class="badge text-bg-light border me-2">{{ ucfirst($step['kind'] ?? 'step') }}</span>
                                    <span class="fw-semibold">{{ $step['label'] ?? 'Untitled step' }}</span>
                                    @php
                                        $nextSteps = collect($step['connections'] ?? [])
                                            ->map(fn ($id) => $stepsById->get($id)['label'] ?? null)
                                            ->filter();
                                    @endphp
                                    @if ($nextSteps->isNotEmpty())
                                        <div class="small text-body-secondary mt-1">Next: {{ $nextSteps->join(', ') }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="small text-body-secondary mt-2 mb-0">No steps have been recorded for this flow chart.</p>
                    @endif
                </article>
                <div class="modal fade" id="customer-flowchart-{{ $flowChart->id }}" tabindex="-1"
                    aria-labelledby="customer-flowchart-title-{{ $flowChart->id }}" aria-hidden="true"
                    data-customer-flowchart-modal>
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title h5" id="customer-flowchart-title-{{ $flowChart->id }}">{{ $flowChart->title }}</h2>
                                    @if ($flowChart->description)
                                        <p class="small text-body-secondary mb-0 mt-1">{{ $flowChart->description }}</p>
                                    @endif
                                </div>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if ($flowSteps->isNotEmpty())
                                    <div class="flow-chart-viewport" data-customer-flowchart>
                                        <div class="flow-chart-canvas">
                                            <svg class="flow-chart-wires" aria-hidden="true">
                                                <defs>
                                                    <marker id="customer-flowchart-arrow-{{ $flowChart->id }}" viewBox="0 0 10 10"
                                                        markerWidth="12" markerHeight="12" refX="10" refY="5"
                                                        orient="auto" markerUnits="userSpaceOnUse">
                                                        <path d="M 0 0 L 10 5 L 0 10 z"></path>
                                                    </marker>
                                                </defs>
                                            </svg>
                                            @foreach ($flowSteps as $index => $step)
                                                <article class="flow-chart-node-card flow-chart-node-{{ $step['kind'] ?? 'page' }}"
                                                    data-node-id="{{ $step['id'] ?? $index }}"
                                                    data-node-index="{{ $index }}"
                                                    data-connections="{{ json_encode($step['connections'] ?? []) }}"
                                                    data-connection-sides="{{ json_encode($step['connection_sides'] ?? []) }}"
                                                    data-position-x="{{ $step['x'] ?? '' }}"
                                                    data-position-y="{{ $step['y'] ?? '' }}">
                                                    <span class="flow-chart-node-kind">{{ ($step['kind'] ?? 'step') === 'page' && ! $flowChart->isWebsite() ? 'step' : ($step['kind'] ?? 'step') }}</span>
                                                    <strong>{{ $step['label'] ?? 'Untitled step' }}</strong>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <p class="text-body-secondary mb-0">No steps have been recorded for this flow chart.</p>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-body-secondary mb-0">No flow charts or SOPs have been linked to this project.</p>
            @endforelse
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-technologies-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-technologies-heading">Project technology stack</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead><tr><th>Technology</th><th>Version</th><th>Notes</th></tr></thead>
                <tbody>
                    @forelse ($project->technologyStack as $technology)
                        <tr>
                            <th scope="row">{{ $technology->name }}</th>
                            <td>{{ $technology->version ?: '—' }}</td>
                            <td>{{ $technology->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-body-secondary py-4">No technologies have been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-parts-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-parts-heading">Parts &amp; hardware</h2>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->inventoryParts as $part)
                <article class="list-group-item">
                    <h3 class="h6 mb-1">{{ $part->name }}</h3>
                    <p class="small text-body-secondary mb-0">{{ $part->pivot->quantity }} {{ \Illuminate\Support\Str::plural('unit', $part->pivot->quantity) }} recommended</p>
                    @if ($part->description)<p class="small mt-2 mb-0">{{ $part->description }}</p>@endif
                </article>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No parts or hardware have been recommended.</p>
            @endforelse
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-costs-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-costs-heading">Software &amp; other project costs</h2>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->estimateItems as $item)
                <article class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <span class="badge text-bg-light border">{{ ucfirst($item->category) }}</span>
                        <span class="fw-semibold ms-2">{{ $item->description }}</span>
                        <div class="small text-body-secondary mt-1">{{ $item->quantity }} × RM {{ number_format((float) $item->unit_price, 2) }}</div>
                    </div>
                    <strong>RM {{ number_format($item->line_total, 2) }}</strong>
                </article>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No software or other project costs have been recorded.</p>
            @endforelse
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-assignees-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-assignees-heading">Assigned people</h2>
        </div>
        <div class="card-body">
            @forelse ($project->assignees as $assignee)
                <span class="badge text-bg-light border me-1 mb-1">{{ $assignee->name }}</span>
            @empty
                <p class="text-body-secondary mb-0">No people are currently assigned to this project.</p>
            @endforelse
        </div>
    </div>

    @php
        $ganttTasks = $project->tasks->flatMap(fn ($task) => collect([$task])->concat($task->subtasks))->values();
        $datedGanttTasks = $ganttTasks->filter(fn ($task) => $task->start_date || $task->due_date);
        $ganttStart = $datedGanttTasks->map(fn ($task) => $task->start_date ?? $task->due_date)
            ->sortBy(fn ($date) => $date->timestamp)->first();
        $ganttEnd = $datedGanttTasks->map(fn ($task) => $task->due_date ?? $task->start_date)
            ->sortByDesc(fn ($date) => $date->timestamp)->first();
        $ganttDays = $ganttStart && $ganttEnd ? max(1, (int) $ganttStart->diffInDays($ganttEnd) + 1) : 0;
    @endphp
    <div class="card mb-4" aria-labelledby="customer-project-gantt-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-gantt-heading">Task Gantt chart</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead><tr><th>Task</th><th>Status</th><th>Schedule</th></tr></thead>
                <tbody>
                    @forelse ($ganttTasks as $task)
                        @php
                            $barStart = $task->start_date ?? $task->due_date;
                            $barEnd = $task->due_date ?? $task->start_date;
                            $barOffset = $barStart && $ganttStart ? (int) $ganttStart->diffInDays($barStart) : 0;
                            $barDuration = $barStart && $barEnd ? (int) $barStart->diffInDays($barEnd) + 1 : 0;
                            $barWidth = $ganttDays > 0 ? max(1, $barDuration / $ganttDays * 100) : 0;
                            $barLeft = $ganttDays > 0 ? $barOffset / $ganttDays * 100 : 0;
                        @endphp
                        <tr>
                            <th scope="row">
                                @if ($task->parent_task_id)<span class="text-body-secondary me-1" aria-hidden="true">↳</span>@endif
                                {{ $task->title }}
                            </th>
                            <td><x-status-badge :status="$task->status" :label="\App\Models\Task::STATUSES[$task->status] ?? $task->status" /></td>
                            <td style="min-width: 240px;">
                                @if ($barStart && $barEnd && $ganttDays > 0)
                                    <div class="position-relative bg-body-tertiary rounded" style="height: 1.5rem;">
                                        <div class="position-absolute top-0 h-100 rounded bg-{{ \App\Support\StatusBadge::colorClass($task->status) }}"
                                            style="left: {{ number_format($barLeft, 3, '.', '') }}%; width: {{ number_format(min($barWidth, 100 - $barLeft), 3, '.', '') }}%;"
                                            title="{{ $barStart->format('M j, Y') }} – {{ $barEnd->format('M j, Y') }}"></div>
                                    </div>
                                    <span class="small text-body-secondary">{{ $barStart->format('M j') }}–{{ $barEnd->format('M j, Y') }}</span>
                                @else
                                    <span class="small text-body-secondary">Unscheduled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-body-secondary py-4">No tasks are available for the project schedule.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4" aria-labelledby="customer-project-tasks-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="customer-project-tasks-heading">Tasks</h2>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->tasks as $task)
                <article class="list-group-item">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <h3 class="h6 mb-0">{{ $task->title }}</h3>
                        <x-status-badge :status="$task->status" :label="\App\Models\Task::STATUSES[$task->status] ?? $task->status" />
                    </div>
                    @if ($task->description)<p class="small text-body-secondary mt-2 mb-2">{{ $task->description }}</p>@endif
                    <div class="d-flex flex-wrap gap-2 small text-body-secondary">
                        <span>{{ \App\Models\Task::PRIORITIES[$task->priority] ?? ucfirst($task->priority) }} priority</span>
                        @if ($task->start_date)<span>Starts {{ $task->start_date->format('M j, Y') }}</span>@endif
                        @if ($task->due_date)<span>Due {{ $task->due_date->format('M j, Y') }}</span>@endif
                        @if ($task->assignees->isNotEmpty())<span>Assigned to {{ $task->assignees->pluck('name')->join(', ') }}</span>@endif
                    </div>
                    @if ($task->subtasks->isNotEmpty())
                        <ul class="list-group list-group-flush mt-3">
                            @foreach ($task->subtasks as $subtask)
                                <li class="list-group-item px-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="text-body-secondary" aria-hidden="true">↳</span>
                                        <span class="fw-semibold">{{ $subtask->title }}</span>
                                        <x-status-badge :status="$subtask->status" :label="\App\Models\Task::STATUSES[$subtask->status] ?? $subtask->status" />
                                        @if ($subtask->assignees->isNotEmpty())
                                            <span class="small text-body-secondary">Assigned to {{ $subtask->assignees->pluck('name')->join(', ') }}</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No project tasks have been added yet.</p>
            @endforelse
        </div>
    </div>

    @include('projects._billing-documents', ['project' => $project, 'billingDocuments' => $billingDocuments, 'canManageBilling' => false])

    @if ($project->links->isNotEmpty())
        <div class="card mb-4" aria-labelledby="customer-project-links-heading">
            <div class="card-header bg-body">
                <h2 class="h5 mb-0" id="customer-project-links-heading">Project links</h2>
            </div>
            <div class="list-group list-group-flush">
                @foreach ($project->links as $link)
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                        href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">
                        <span>{{ $link->label }}</span>
                        <i class="bi bi-box-arrow-up-right text-body-secondary" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    (() => {
        const sideVector = {
            top: { x: 0, y: -1 },
            bottom: { x: 0, y: 1 },
            left: { x: -1, y: 0 },
            right: { x: 1, y: 0 },
        };
        const oppositeSide = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };

        document.querySelectorAll('[data-customer-flowchart-modal]').forEach((modal) => {
            modal.addEventListener('shown.bs.modal', () => {
                const viewport = modal.querySelector('[data-customer-flowchart]');
                if (!viewport) return;

                const canvas = viewport.querySelector('.flow-chart-canvas');
                const svg = canvas.querySelector('.flow-chart-wires');
                const nodes = [...canvas.querySelectorAll('.flow-chart-node-card')];
                if (!nodes.length) return;

                const connections = new Map(nodes.map((node) => [
                    node.dataset.nodeId,
                    JSON.parse(node.dataset.connections || '[]'),
                ]));
                const connectionSides = new Map(nodes.map((node) => [
                    node.dataset.nodeId,
                    JSON.parse(node.dataset.connectionSides || '{}'),
                ]));
                const levels = new Map();
                const incoming = new Map(nodes.map((node) => [node.dataset.nodeId, 0]));
                connections.forEach((targets) => targets.forEach((targetId) => {
                    if (incoming.has(targetId)) incoming.set(targetId, incoming.get(targetId) + 1);
                }));
                const placeComponent = (start) => {
                    if (levels.has(start.dataset.nodeId)) return;
                    const queue = [start];
                    levels.set(start.dataset.nodeId, 0);
                    for (let index = 0; index < queue.length; index += 1) {
                        const source = queue[index];
                        (connections.get(source.dataset.nodeId) || []).forEach((targetId) => {
                            if (levels.has(targetId)) return;
                            if (!nodes.some((node) => node.dataset.nodeId === targetId)) return;
                            levels.set(targetId, levels.get(source.dataset.nodeId) + 1);
                            queue.push(nodes.find((node) => node.dataset.nodeId === targetId));
                        });
                    }
                };

                nodes.filter((node) => incoming.get(node.dataset.nodeId) === 0).forEach(placeComponent);
                nodes.forEach(placeComponent);

                const rows = new Map();
                nodes.forEach((node) => {
                    const level = levels.get(node.dataset.nodeId);
                    if (!rows.has(level)) rows.set(level, []);
                    rows.get(level).push(node);
                });
                let width = Math.max(viewport.clientWidth - 2, ...[...rows.values()].map((row) => row.length * 250 + 80));
                rows.forEach((row, level) => {
                    const rowWidth = row.length * 250;
                    const startX = Math.max(40, (width - rowWidth) / 2);
                    row.forEach((node, index) => {
                        node.style.left = node.dataset.positionX !== ''
                            ? `${Number(node.dataset.positionX)}px`
                            : `${startX + index * 250}px`;
                        node.style.top = node.dataset.positionY !== ''
                            ? `${Number(node.dataset.positionY)}px`
                            : `${40 + level * 340}px`;
                    });
                });

                width = Math.max(width, ...nodes.map((node) => node.offsetLeft + node.offsetWidth + 40));
                const height = Math.max(220, ...nodes.map((node) => node.offsetTop + node.offsetHeight + 40));
                canvas.style.width = `${width}px`;
                canvas.style.height = `${height}px`;
                svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
                svg.setAttribute('width', width);
                svg.setAttribute('height', height);
                svg.querySelectorAll('.flow-chart-wire').forEach((wire) => wire.remove());

                nodes.forEach((source) => {
                    (connections.get(source.dataset.nodeId) || []).forEach((targetId) => {
                        const target = nodes.find((node) => node.dataset.nodeId === targetId);
                        if (!target) return;
                        const dx = target.offsetLeft + target.offsetWidth / 2 - (source.offsetLeft + source.offsetWidth / 2);
                        const dy = target.offsetTop + target.offsetHeight / 2 - (source.offsetTop + source.offsetHeight / 2);
                        const savedSides = connectionSides.get(source.dataset.nodeId)[targetId];
                        const sourceSide = (typeof savedSides === 'object' ? savedSides?.source : savedSides)
                            || (Math.abs(dx) > Math.abs(dy) ? (dx >= 0 ? 'right' : 'left') : (dy >= 0 ? 'bottom' : 'top'));
                        const targetSide = (typeof savedSides === 'object' ? savedSides?.target : null) || oppositeSide[sourceSide];
                        const sourceVector = sideVector[sourceSide];
                        const targetVector = sideVector[targetSide];
                        if (!sourceVector || !targetVector) return;
                        const sourceX = source.offsetLeft + source.offsetWidth / 2 + sourceVector.x * source.offsetWidth / 2;
                        const sourceY = source.offsetTop + source.offsetHeight / 2 + sourceVector.y * source.offsetHeight / 2;
                        const targetX = target.offsetLeft + target.offsetWidth / 2 + targetVector.x * (target.offsetWidth / 2 + 9);
                        const targetY = target.offsetTop + target.offsetHeight / 2 + targetVector.y * (target.offsetHeight / 2 + 9);
                        const control = Math.max(40, Math.min(150, Math.hypot(targetX - sourceX, targetY - sourceY) * 0.45));
                        const wire = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        wire.setAttribute('class', 'flow-chart-wire');
                        wire.setAttribute('d', `M ${sourceX} ${sourceY} C ${sourceX + sourceVector.x * control} ${sourceY + sourceVector.y * control}, ${targetX + targetVector.x * control} ${targetY + targetVector.y * control}, ${targetX} ${targetY}`);
                        wire.setAttribute('marker-end', `url(#customer-flowchart-arrow-${modal.id.replace('customer-flowchart-', '')})`);
                        svg.append(wire);
                    });
                });
            });
        });
    })();
</script>
@endpush
