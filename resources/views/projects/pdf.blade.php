<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} - Project Details</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #222; }
        h1 { font-size: 20pt; margin-bottom: 4pt; }
        h2 { font-size: 14pt; margin: 20pt 0 8pt; border-bottom: 1px solid #888; padding-bottom: 4pt; }
        h3 { font-size: 11pt; margin-bottom: 4pt; }
        p { margin: 4pt 0; }
        table { width: 100%; border-collapse: collapse; margin: 6pt 0 12pt; }
        th, td { border: 1px solid #bbb; text-align: left; padding: 5pt; vertical-align: top; }
        th { background: #f0f0f0; }
        .muted { color: #666; }
        .flow-chart-page { page-break-before: always; }
        .flow-chart-diagram { display: block; margin: 8pt auto 0; }
    </style>
</head>
<body>
    <h1>{{ $project->name }}</h1>
    <p class="muted">Project details · Generated {{ now()->format('F j, Y') }}</p>

    @if (in_array('overview', $sections, true))
        <h2>Project overview</h2>
        <p>{{ $project->description ?: 'No project description provided.' }}</p>
        <p><strong>Created:</strong> {{ $project->created_at?->format('F j, Y') ?? '—' }}</p>
    @endif

    @if (in_array('customers', $sections, true))
        <h2>Customers</h2>
        @forelse ($project->customers as $customer)
            <p><strong>{{ $customer->name }}</strong>
                @if ($customer->email) · {{ $customer->email }}@endif
                @if ($customer->phone) · {{ $customer->phone }}@endif
            </p>
        @empty
            <p class="muted">No customers are linked to this project.</p>
        @endforelse
    @endif

    @if (in_array('technology_stack', $sections, true))
        <h2>Project technology stack</h2>
        @if ($project->technologyStack->isEmpty())
            <p class="muted">No technologies have been recorded for this project.</p>
        @else
            <table>
                <thead>
                    <tr><th>Technology</th><th>Version</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    @foreach ($project->technologyStack as $technology)
                        <tr><td>{{ $technology->name }}</td><td>{{ $technology->version ?: '—' }}</td><td>{{ $technology->notes ?: '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if (in_array('estimates', $sections, true))
        <h2>Software and other estimates</h2>
        @if ($project->estimateItems->isEmpty())
            <p class="muted">No estimate items have been recorded.</p>
        @else
            <table>
                <thead>
                    <tr><th>Category</th><th>Description</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($project->estimateItems as $item)
                        <tr>
                            <td>{{ ucfirst($item->category) }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>RM {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>RM {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if (in_array('schedule', $sections, true))
        <h2>Project schedule</h2>
        @if ($project->events->isEmpty())
            <p class="muted">No scheduled events have been recorded.</p>
        @else
            <table>
                <thead>
                    <tr><th>Event</th><th>Type</th><th>Starts</th><th>Ends</th><th>Location</th><th>Description</th></tr>
                </thead>
                <tbody>
                    @foreach ($project->events as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ \App\Models\Event::TYPES[$event->event_type] ?? $event->event_type }}</td>
                            <td>{{ $event->starts_at->format('M j, Y g:i A') }}</td>
                            <td>{{ $event->ends_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td>{{ $event->location ?: '—' }}</td>
                            <td>{{ $event->description ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if (in_array('gantt', $sections, true))
        <h2>Task Gantt chart</h2>
        @php
            $ganttTasks = $project->tasks->flatMap(fn ($task) => collect([$task])->concat($task->subtasks))->values();
            $datedGanttTasks = $ganttTasks->filter(fn ($task) => $task->start_date || $task->due_date);
            $ganttStart = $datedGanttTasks->map(fn ($task) => $task->start_date ?? $task->due_date)->sortBy(fn ($date) => $date->timestamp)->first();
            $ganttEnd = $datedGanttTasks->map(fn ($task) => $task->due_date ?? $task->start_date)->sortByDesc(fn ($date) => $date->timestamp)->first();
            $ganttDays = $ganttStart && $ganttEnd ? max(1, (int) $ganttStart->diffInDays($ganttEnd) + 1) : 0;
            $scheduledGanttTasks = $datedGanttTasks->values();
            $ganttViewWidth = 1000;
            $ganttPlotX = 285;
            $ganttPlotWidth = 700;
            $ganttAxisHeight = 54;
            $ganttRowHeight = 34;
            $ganttTickCount = min(7, max(2, $ganttDays));
            $ganttTickOffsets = collect(range(0, $ganttTickCount - 1))
                ->map(fn ($index) => (int) round($index * max(0, $ganttDays - 1) / max(1, $ganttTickCount - 1)));
            $ganttTaskPages = $scheduledGanttTasks->chunk(22)->values();
        @endphp
        @if ($ganttTasks->isEmpty())
            <p class="muted">No tasks are available for this project.</p>
        @elseif ($scheduledGanttTasks->isEmpty())
            <p class="muted">No task dates have been set.</p>
            <ul>
                @foreach ($ganttTasks as $ganttTask)
                    <li>{{ $ganttTask->title }} — <span style="display:inline-block;padding:2px 6px;border-radius:3px;color:#fff;background-color:{{ \App\Support\StatusBadge::ganttPdfColor($ganttTask->status) }}">{{ \App\Models\Task::STATUSES[$ganttTask->status] ?? $ganttTask->status }}</span> — Unscheduled</li>
                @endforeach
            </ul>
        @else
            <p class="muted">{{ $ganttStart->format('M j, Y') }} – {{ $ganttEnd->format('M j, Y') }}</p>
            <p class="muted">Status:
                <span style="color: {{ \App\Support\StatusBadge::ganttPdfColor('done') }};">■ Done</span> ·
                <span style="color: {{ \App\Support\StatusBadge::ganttPdfColor('in_progress') }};">■ In progress</span> ·
                <span style="color: {{ \App\Support\StatusBadge::ganttPdfColor('backlog') }};">■ To do</span>
            </p>
            @foreach ($ganttTaskPages as $ganttPage)
                @php
                    $ganttPageHeight = $ganttAxisHeight + $ganttPage->count() * $ganttRowHeight;
                    $ganttPageHeightMm = $ganttPageHeight * .18;
                @endphp
                <svg class="project-gantt-chart" xmlns="http://www.w3.org/2000/svg"
                    width="175mm" height="{{ number_format($ganttPageHeightMm, 2, '.', '') }}mm"
                    viewBox="0 0 {{ $ganttViewWidth }} {{ $ganttPageHeight }}"
                    style="{{ $loop->first ? '' : 'page-break-before: always;' }}">
                    <rect x="{{ $ganttPlotX }}" y="0" width="{{ $ganttPlotWidth }}" height="{{ $ganttPageHeight }}" fill="#ffffff"></rect>
                    <text x="5" y="20" font-family="sans-serif" font-size="14" font-weight="bold" fill="#344050">Task</text>
                    <text x="{{ $ganttPlotX }}" y="20" font-family="sans-serif" font-size="14" font-weight="bold" fill="#344050">Schedule</text>
                    @foreach ($ganttPage as $index => $ganttTask)
                        @if ($index % 2 === 1)
                            <rect x="0" y="{{ $ganttAxisHeight + $index * $ganttRowHeight }}"
                                width="{{ $ganttViewWidth }}" height="{{ $ganttRowHeight }}" fill="#f7f9fc"></rect>
                        @endif
                    @endforeach
                    @foreach ($ganttTickOffsets as $tickOffset)
                        @php
                            $tickX = $ganttPlotX + ($ganttDays > 1 ? $tickOffset / ($ganttDays - 1) * $ganttPlotWidth : 0);
                            $tickDate = $ganttStart->copy()->addDays($tickOffset);
                        @endphp
                        <line x1="{{ $tickX }}" y1="30" x2="{{ $tickX }}" y2="{{ $ganttPageHeight }}"
                            stroke="#d8dee8" stroke-width="1"></line>
                        <text x="{{ $tickX }}" y="44"
                            text-anchor="{{ $loop->first ? 'start' : ($loop->last ? 'end' : 'middle') }}"
                            font-family="sans-serif" font-size="11" fill="#5e6e82">{{ $tickDate->format('M j') }}</text>
                    @endforeach
                    @foreach ($ganttPage as $index => $ganttTask)
                        @php
                            $barStart = $ganttTask->start_date ?? $ganttTask->due_date;
                            $barEnd = $ganttTask->due_date ?? $ganttTask->start_date;
                            $barOffset = (int) $ganttStart->diffInDays($barStart);
                            $barDuration = (int) $barStart->diffInDays($barEnd) + 1;
                            $barX = $ganttPlotX + ($ganttDays > 1 ? $barOffset / ($ganttDays - 1) * $ganttPlotWidth : 0);
                            $barWidth = max(8, $barDuration / $ganttDays * $ganttPlotWidth);
                            $barWidth = min($barWidth, $ganttPlotX + $ganttPlotWidth - $barX);
                            $rowY = $ganttAxisHeight + $index * $ganttRowHeight;
                            $barColor = \App\Support\StatusBadge::ganttPdfColor($ganttTask->status);
                        @endphp
                        <text x="{{ $ganttTask->parent_task_id ? 18 : 5 }}" y="{{ $rowY + 15 }}"
                            font-family="sans-serif" font-size="12" font-weight="bold" fill="#344050"
                            textLength="260" lengthAdjust="spacingAndGlyphs">{{ $ganttTask->title }}</text>
                        <text x="{{ $ganttTask->parent_task_id ? 18 : 5 }}" y="{{ $rowY + 28 }}"
                            font-family="sans-serif" font-size="10" fill="{{ \App\Support\StatusBadge::ganttPdfColor($ganttTask->status) }}">{{ \App\Models\Task::STATUSES[$ganttTask->status] ?? $ganttTask->status }}</text>
                        <rect x="{{ number_format($barX, 2, '.', '') }}" y="{{ $rowY + 7 }}"
                            width="{{ number_format($barWidth, 2, '.', '') }}" height="20" rx="6"
                            fill="{{ $barColor }}">
                            <title>{{ $ganttTask->title }}: {{ $barStart->format('M j, Y') }} – {{ $barEnd->format('M j, Y') }}</title>
                        </rect>
                    @endforeach
                </svg>
            @endforeach
            @php
                $unscheduledGanttTasks = $ganttTasks->reject(fn ($task) => $task->start_date || $task->due_date);
            @endphp
            @if ($unscheduledGanttTasks->isNotEmpty())
                <p class="muted">Unscheduled tasks: {{ $unscheduledGanttTasks->pluck('title')->implode(', ') }}</p>
            @endif
        @endif
    @endif

    @if (in_array('flowcharts', $sections, true))
        @if ($flowCharts->isEmpty())
            <h2>Flow charts and SOPs</h2>
        @endif
        @forelse ($flowCharts as $flowChart)
            <div class="flow-chart-page">
                @php
                    $diagramSteps = collect($flowChart->steps ?? [])->values();
                    $diagramNodeWidth = 190;
                    $diagramNodeHeight = 58;
                    $diagramPositions = [];
                    foreach ($diagramSteps as $index => $step) {
                        $diagramPositions[$step['id'] ?? (string) $index] = [
                            'x' => isset($step['x']) ? (int) $step['x'] : 255,
                            'y' => isset($step['y']) ? (int) $step['y'] : 24 + $index * 100,
                        ];
                    }
                    $diagramMinX = $diagramSteps->isEmpty() ? 0 : min(array_column($diagramPositions, 'x'));
                    $diagramMinY = $diagramSteps->isEmpty() ? 0 : min(array_column($diagramPositions, 'y'));
                    $diagramWidth = $diagramSteps->isEmpty() ? 1 : max(array_column($diagramPositions, 'x')) + $diagramNodeWidth + 20 - $diagramMinX;
                    $diagramHeight = $diagramSteps->isEmpty() ? 1 : max(array_column($diagramPositions, 'y')) + $diagramNodeHeight + 20 - $diagramMinY;
                    $diagramScale = min(175 / $diagramWidth, 230 / $diagramHeight);
                    $diagramPhysicalWidth = $diagramWidth * $diagramScale;
                    $diagramPhysicalHeight = $diagramHeight * $diagramScale;
                @endphp
                <h2>{{ $flowChart->title }}</h2>
                <p class="muted">{{ ucfirst($flowChart->category) }}@if ($flowChart->subject_name) · {{ $flowChart->subject_name }}@endif</p>
                @if ($flowChart->description)<p>{{ $flowChart->description }}</p>@endif
                @if ($diagramSteps->isEmpty())
                    <p class="muted">No steps are recorded for this flow chart.</p>
                @else
                    <svg class="flow-chart-diagram" xmlns="http://www.w3.org/2000/svg"
                        width="{{ number_format($diagramPhysicalWidth, 2, '.', '') }}mm"
                        height="{{ number_format($diagramPhysicalHeight, 2, '.', '') }}mm"
                        viewBox="0 0 {{ $diagramWidth }} {{ $diagramHeight }}">
                        <defs>
                            <marker id="arrow-{{ $flowChart->id }}" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                                <path d="M0,0 L8,4 L0,8 Z" fill="#64748b"></path>
                            </marker>
                        </defs>
                        @foreach ($diagramSteps as $index => $step)
                            @php
                                $sourceId = $step['id'] ?? (string) $index;
                                $sourcePosition = $diagramPositions[$sourceId];
                                $sourceX = $sourcePosition['x'] - $diagramMinX + $diagramNodeWidth / 2;
                                $sourceY = $sourcePosition['y'] - $diagramMinY + $diagramNodeHeight;
                            @endphp
                            @foreach ($step['connections'] ?? [] as $targetId)
                                @if (isset($diagramPositions[$targetId]))
                                    @php
                                        $targetPosition = $diagramPositions[$targetId];
                                        $targetX = $targetPosition['x'] - $diagramMinX + $diagramNodeWidth / 2;
                                        $targetY = $targetPosition['y'] - $diagramMinY;
                                    @endphp
                                    <path d="M {{ $sourceX }} {{ $sourceY }} C {{ $sourceX }} {{ $sourceY + 24 }}, {{ $targetX }} {{ $targetY - 24 }}, {{ $targetX }} {{ $targetY }}"
                                        fill="none" stroke="#64748b" stroke-width="2" marker-end="url(#arrow-{{ $flowChart->id }})"></path>
                                @endif
                            @endforeach
                        @endforeach
                        @foreach ($diagramSteps as $index => $step)
                            @php
                                $nodeId = $step['id'] ?? (string) $index;
                                $position = $diagramPositions[$nodeId];
                                $nodeX = $position['x'] - $diagramMinX;
                                $nodeY = $position['y'] - $diagramMinY;
                                $nodeFill = match ($step['kind'] ?? 'page') {
                                    'start' => '#dcfce7',
                                    'end' => '#fee2e2',
                                    'decision' => '#fef3c7',
                                    default => '#eff6ff',
                                };
                            @endphp
                            <rect x="{{ $nodeX }}" y="{{ $nodeY }}" width="{{ $diagramNodeWidth }}" height="{{ $diagramNodeHeight }}"
                                rx="8" fill="{{ $nodeFill }}" stroke="#475569" stroke-width="1.5"></rect>
                            <text x="{{ $nodeX + $diagramNodeWidth / 2 }}" y="{{ $nodeY + 24 }}"
                                text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold"
                                fill="#1e293b" textLength="{{ $diagramNodeWidth - 20 }}" lengthAdjust="spacingAndGlyphs">{{ $step['label'] ?? 'Step' }}</text>
                            <text x="{{ $nodeX + $diagramNodeWidth / 2 }}" y="{{ $nodeY + 44 }}"
                                text-anchor="middle" font-family="sans-serif" font-size="10" fill="#64748b">{{ ucfirst($step['kind'] ?? 'page') }}</text>
                        @endforeach
                    </svg>
                @endif
            </div>
        @empty
            <p class="muted">No selected flow charts are linked to this project.</p>
        @endforelse
    @endif

    <p class="muted" style="margin-top: 24pt;">
        @if (in_array('gantt', $sections, true))
            Task details are not included; the selected Gantt chart shows schedule dates only.
        @else
            Tasks and task timelines are not included in this export.
        @endif
    </p>
</body>
</html>
