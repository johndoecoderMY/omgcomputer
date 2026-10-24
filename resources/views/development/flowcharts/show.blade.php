@extends('layouts.app')

@section('title', $flowChart->title)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" :title="$flowChart->title"
        :description="$flowChart->description"
        :back-url="route('development.flowcharts.index')" back-label="All flow charts">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('development.flowcharts.edit', $flowChart) }}">
                <i class="bi bi-pencil me-2"></i>Edit
            </a>
            <form id="flow-chart-layout-form" method="POST" action="{{ route('development.flowcharts.layout', $flowChart) }}">
                @csrf
                @method('PATCH')
                <div id="flow-chart-layout-inputs"></div>
                <button class="btn btn-primary" type="submit" disabled><i class="bi bi-floppy me-2"></i>Save layout</button>
            </form>
            <form method="POST" action="{{ route('development.flowcharts.destroy', $flowChart) }}"
                onsubmit="return confirm('Delete this flow chart?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">Delete</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    @if ($flowChart->is_example)
        <div class="alert alert-info" role="status">This example can be copied from a project page. Its steps are not assigned to separate projects.</div>
    @else
        <div class="alert alert-info" role="status">Link the complete flow chart to a project, then add its steps as tasks for that project.</div>
    @endif

    <div class="card">        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h2 class="h5 mb-0">{{ $flowChart->isWebsite() ? $flowChart->categoryLabel() : 'Process steps' }}</h2>
                @if ($flowChart->is_example)<span class="badge text-bg-info">Example</span>@endif
            </div>
            <span class="badge text-bg-light border">{{ count($flowChart->steps) }} steps</span>
        </div>
        <div class="card-body p-4">
            <div class="flow-chart-viewport" data-flow-chart>
                <div class="flow-chart-canvas">
                    <svg class="flow-chart-wires" aria-hidden="true">
                        <defs>
                            <marker id="flow-chart-arrow" viewBox="0 0 10 10" markerWidth="12" markerHeight="12"
                                refX="10" refY="5" orient="auto" markerUnits="userSpaceOnUse">
                                <path d="M 0 0 L 10 5 L 0 10 z" fill="var(--falcon-primary)" stroke="none"></path>
                            </marker>
                        </defs>
                    </svg>
                    @foreach ($flowChart->steps as $index => $step)
                        <article class="flow-chart-node-card flow-chart-node-{{ $step['kind'] }}"
                            data-node-id="{{ $step['id'] }}" data-node-index="{{ $index }}"
                            data-connections="{{ json_encode($step['connections'] ?? []) }}"
                            data-connection-sides="{{ json_encode($step['connection_sides'] ?? []) }}"
                            data-position-x="{{ $step['x'] ?? '' }}" data-position-y="{{ $step['y'] ?? '' }}">
                            <button class="flow-chart-drag-handle" type="button" aria-label="Move {{ $step['label'] }} node" title="Move node"><span class="bi bi-grip-horizontal"></span></button>
                            <span class="flow-chart-node-kind">{{ $step['kind'] === 'page' && ! $flowChart->isWebsite() ? 'step' : $step['kind'] }}</span>
                            <strong>{{ $step['label'] }}</strong>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @unless ($flowChart->is_example)
        <div class="card mt-4 p-2">
            <div class="card-header bg-body">
                <h2 class="h5 mb-0">Add flow chart steps to tasks</h2>
            </div>
            <div class="card-body">
                @if ($linkedProjects->isEmpty())
                    <p class="text-body-secondary mb-0">Link this flow chart to a project before adding its steps as tasks.</p>
                @else
                    <p class="text-body-secondary">Creates one task per step in a single linked project. Existing tasks from these steps are kept and not duplicated.</p>
                    <form method="POST" action="{{ route('development.flowcharts.tasks.store', $flowChart) }}"
                        class="d-flex flex-wrap align-items-end gap-2">
                        @csrf
                        <div>
                            <label class="form-label mb-1" for="flow-chart-task-project">Project</label>
                            <select class="form-select" id="flow-chart-task-project" name="project_id" required>
                                @foreach ($linkedProjects as $project)
                                    <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Add steps as tasks</button>
                    </form>
                    @error('project_id')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                @endif
                @if ($stepTasks->isNotEmpty())
                    <h3 class="h6 mt-4">Tasks created from this flow chart</h3>
                    <ul class="mb-0">
                        @foreach ($stepTasks as $task)
                            <li>
                                <a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                                <span class="text-body-secondary">— {{ $task->project->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endunless
    <div class="card mt-4">        <div class="card-header bg-body"><h2 class="h5 mb-0">Linked projects</h2></div>
        <div class="card-body">
            @if ($linkedProjects->isEmpty())
                <p class="text-body-secondary mb-0">This flow chart is not linked to a project yet. Add it from a project page.</p>
            @else
                <ul class="mb-0">@foreach ($linkedProjects as $project)<li><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>@endforeach</ul>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
.flow-chart-drag-handle{position:absolute;top:.35rem;right:.45rem;border:0;background:transparent;color:var(--falcon-gray-600);cursor:grab;padding:.2rem .35rem;touch-action:none}
.flow-chart-drag-handle:active,.flow-chart-node-card.is-dragging .flow-chart-drag-handle{cursor:grabbing;color:var(--falcon-primary)}
.flow-chart-node-card.is-dragging{z-index:3;box-shadow:0 .5rem 1.25rem rgba(0,0,0,.2)}
</style>
<script>
    (() => {
        const viewport = document.querySelector('[data-flow-chart]');
        if (!viewport) return;

        const canvas = viewport.querySelector('.flow-chart-canvas');
        const svg = canvas.querySelector('.flow-chart-wires');
        const nodes = [...canvas.querySelectorAll('.flow-chart-node-card')];
        const layoutForm = document.getElementById('flow-chart-layout-form');
        const layoutInputs = document.getElementById('flow-chart-layout-inputs');
        if (!nodes.length) return;

        const sideVector = (side) => ({
            top: { x: 0, y: -1 },
            bottom: { x: 0, y: 1 },
            left: { x: -1, y: 0 },
            right: { x: 1, y: 0 },
        })[side];
        const oppositeSide = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };
        const nodePort = (node, side, offset) => {
            const vector = sideVector(side);
            const centerX = node.offsetLeft + node.offsetWidth / 2;
            const centerY = node.offsetTop + node.offsetHeight / 2;
            return {
                x: centerX + vector.x * (node.offsetWidth / 2 + offset),
                y: centerY + vector.y * (node.offsetHeight / 2 + offset),
            };
        };

        const drawWires = () => {
            const width = Math.max(viewport.clientWidth - 2, canvas.scrollWidth);
            const height = Math.max(220, canvas.scrollHeight);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);
            svg.querySelectorAll('.flow-chart-wire').forEach((wire) => wire.remove());

            const connections = new Map(nodes.map((node) => [
                node.dataset.nodeId,
                JSON.parse(node.dataset.connections || '[]'),
            ]));
            const connectionSides = new Map(nodes.map((node) => [
                node.dataset.nodeId,
                JSON.parse(node.dataset.connectionSides || '{}'),
            ]));
            nodes.forEach((source) => {
                (connections.get(source.dataset.nodeId) || []).forEach((targetId) => {
                    const target = nodes.find((node) => node.dataset.nodeId === targetId);
                    if (!target) return;
                    const dx = target.offsetLeft + target.offsetWidth / 2 - (source.offsetLeft + source.offsetWidth / 2);
                    const dy = target.offsetTop + target.offsetHeight / 2 - (source.offsetTop + source.offsetHeight / 2);
                    const savedSides = connectionSides.get(source.dataset.nodeId)[targetId];
                    const sourceSide = (typeof savedSides === 'object' ? savedSides?.source : savedSides)
                        || (Math.abs(dx) > Math.abs(dy) ? (dx >= 0 ? 'right' : 'left') : (dy >= 0 ? 'bottom' : 'top'));
                    const targetSide = (typeof savedSides === 'object' ? savedSides?.target : null)
                        || oppositeSide[sourceSide];
                    const sourcePoint = nodePort(source, sourceSide, 0);
                    const targetPoint = nodePort(target, targetSide, 9);
                    const distance = Math.hypot(targetPoint.x - sourcePoint.x, targetPoint.y - sourcePoint.y);
                    const control = Math.max(40, Math.min(150, distance * 0.45));
                    const sourceVector = sideVector(sourceSide);
                    const targetVector = sideVector(targetSide);
                    const wire = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    wire.setAttribute('class', 'flow-chart-wire');
                    wire.setAttribute('d', `M ${sourcePoint.x} ${sourcePoint.y} C ${sourcePoint.x + sourceVector.x * control} ${sourcePoint.y + sourceVector.y * control}, ${targetPoint.x + targetVector.x * control} ${targetPoint.y + targetVector.y * control}, ${targetPoint.x} ${targetPoint.y}`);
                    wire.setAttribute('marker-end', 'url(#flow-chart-arrow)');
                    svg.append(wire);
                });
            });
        };

        const render = () => {
            const gapX = 250;
            const gapY = 340;
            const padding = 40;
            const connections = new Map(nodes.map((node) => [
                node.dataset.nodeId,
                JSON.parse(node.dataset.connections || '[]'),
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
                    const nextLevel = levels.get(source.dataset.nodeId) + 1;
                    (connections.get(source.dataset.nodeId) || []).forEach((targetId) => {
                        if (levels.has(targetId)) return;
                        const target = nodes.find((node) => node.dataset.nodeId === targetId);
                        if (!target) return;
                        levels.set(targetId, nextLevel);
                        queue.push(target);
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

            const widestRow = Math.max(...[...rows.values()].map((row) => row.length));
            const width = Math.max(viewport.clientWidth - 2, widestRow * gapX + padding * 2);
            const defaults = new Map();
            rows.forEach((row, level) => {
                const rowWidth = row.length * gapX;
                const startX = Math.max(padding, (width - rowWidth) / 2);
                row.forEach((node, index) => {
                    defaults.set(node.dataset.nodeId, {
                        x: startX + index * gapX,
                        y: padding + level * gapY,
                    });
                });
            });

            nodes.forEach((node) => {
                const fallback = defaults.get(node.dataset.nodeId);
                const x = node.dataset.positionX !== '' ? Number(node.dataset.positionX) : fallback.x;
                const y = node.dataset.positionY !== '' ? Number(node.dataset.positionY) : fallback.y;
                node.style.left = `${x}px`;
                node.style.top = `${y}px`;
            });

            drawWires();
        };

        nodes.forEach((node) => {
            const handle = node.querySelector('.flow-chart-drag-handle');
            let drag = null;

            handle.addEventListener('pointerdown', (event) => {
                if (event.pointerType === 'mouse' && event.button !== 0) return;
                event.preventDefault();
                handle.setPointerCapture(event.pointerId);
                node.classList.add('is-dragging');
                drag = {
                    pointerId: event.pointerId,
                    startX: event.clientX,
                    startY: event.clientY,
                    left: node.offsetLeft,
                    top: node.offsetTop,
                };
            });

            handle.addEventListener('pointermove', (event) => {
                if (!drag || drag.pointerId !== event.pointerId) return;
                const scaleX = canvas.offsetWidth / Math.max(1, canvas.getBoundingClientRect().width);
                const scaleY = canvas.offsetHeight / Math.max(1, canvas.getBoundingClientRect().height);
                const left = Math.max(0, Math.round(drag.left + (event.clientX - drag.startX) * scaleX));
                const top = Math.max(0, Math.round(drag.top + (event.clientY - drag.startY) * scaleY));
                node.style.left = `${left}px`;
                node.style.top = `${top}px`;
                canvas.style.width = `${Math.max(viewport.clientWidth - 2, left + node.offsetWidth + 40, canvas.scrollWidth)}px`;
                canvas.style.height = `${Math.max(220, top + node.offsetHeight + 40, canvas.scrollHeight)}px`;
                drawWires();
            });

            const endDrag = (event) => {
                if (!drag || drag.pointerId !== event.pointerId) return;
                drag = null;
                node.classList.remove('is-dragging');
            };
            handle.addEventListener('pointerup', endDrag);
            handle.addEventListener('pointercancel', endDrag);
        });

        layoutForm.addEventListener('submit', () => {
            layoutInputs.replaceChildren();
            nodes.forEach((node, index) => {
                const fields = {
                    id: node.dataset.nodeId,
                    x: Math.round(parseFloat(node.style.left) || 0),
                    y: Math.round(parseFloat(node.style.top) || 0),
                };
                Object.entries(fields).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `positions[${index}][${key}]`;
                    input.value = value;
                    layoutInputs.append(input);
                });
            });
        });

        render();
        layoutForm.querySelector('button[type="submit"]').disabled = false;
        window.addEventListener('resize', render);
    })();
</script>
@endpush
