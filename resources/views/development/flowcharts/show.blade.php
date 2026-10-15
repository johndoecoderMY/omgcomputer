@extends('layouts.app')

@section('title', $flowChart->title)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" :title="$flowChart->title"
        :description="$flowChart->description"
        :back-url="route('development.flowcharts.index')" back-label="All flow charts">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('development.flowcharts.edit', $flowChart) }}">
                <i class="fas fa-pen me-2"></i>Edit
            </a>
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

    <section class="card">
        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Website structure</h2>
            <span class="badge text-bg-light border">{{ count($flowChart->steps) }} steps</span>
        </div>
        <div class="card-body p-0">
            <div class="flow-chart-viewport" data-flow-chart>
                <div class="flow-chart-canvas">
                    <svg class="flow-chart-wires" aria-hidden="true">
                        <defs>
                            <marker id="flow-chart-arrow" markerWidth="10" markerHeight="10"
                                refX="8" refY="5" orient="auto" markerUnits="strokeWidth">
                                <path d="M 0 0 L 10 5 L 0 10 z"></path>
                            </marker>
                        </defs>
                    </svg>
                    @foreach ($flowChart->steps as $index => $step)
                        <article class="flow-chart-node-card flow-chart-node-{{ $step['kind'] }}"
                            data-node-id="{{ $step['id'] }}" data-node-index="{{ $index }}"
                            data-connections="{{ json_encode($step['connections'] ?? []) }}">
                            <span class="flow-chart-node-kind">{{ $step['kind'] }}</span>
                            <strong>{{ $step['label'] }}</strong>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const viewport = document.querySelector('[data-flow-chart]');
        if (!viewport) return;

        const canvas = viewport.querySelector('.flow-chart-canvas');
        const svg = canvas.querySelector('.flow-chart-wires');
        const nodes = [...canvas.querySelectorAll('.flow-chart-node-card')];
        if (!nodes.length) return;

        const render = () => {
            const gapX = 230;
            const gapY = 170;
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
            const height = Math.max(...rows.keys()) * gapY + 140;
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);

            rows.forEach((row, level) => {
                const rowWidth = row.length * gapX;
                const startX = Math.max(padding, (width - rowWidth) / 2);
                row.forEach((node, index) => {
                    node.style.left = `${startX + index * gapX}px`;
                    node.style.top = `${padding + level * gapY}px`;
                });
            });

            svg.querySelectorAll('.flow-chart-wire').forEach((wire) => wire.remove());
            nodes.forEach((source) => {
                const sourceX = source.offsetLeft + source.offsetWidth / 2;
                const sourceY = source.offsetTop + source.offsetHeight;
                (connections.get(source.dataset.nodeId) || []).forEach((targetId) => {
                    const target = nodes.find((node) => node.dataset.nodeId === targetId);
                    if (!target) return;
                    const targetX = target.offsetLeft + target.offsetWidth / 2;
                    const targetY = target.offsetTop;
                    const bendY = sourceY + Math.max(28, (targetY - sourceY) / 2);
                    const wire = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    wire.setAttribute('class', 'flow-chart-wire');
                    wire.setAttribute('d', `M ${sourceX} ${sourceY} C ${sourceX} ${bendY}, ${targetX} ${bendY}, ${targetX} ${targetY}`);
                    wire.setAttribute('marker-end', 'url(#flow-chart-arrow)');
                    svg.append(wire);
                });
            });
        };

        render();
        window.addEventListener('resize', render);
    })();
</script>
@endpush
