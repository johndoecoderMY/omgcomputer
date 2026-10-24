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
        .project-gantt-chart { display: inline; }
        .flow-chart-diagram { display: inline; margin-top: 8pt; }
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

    @if (in_array('links', $sections, true))
        <h2>Project links</h2>
        @if ($project->links->isEmpty())
            <p class="muted">No project links have been added.</p>
        @else
            <table>
                <thead>
                    <tr><th>Label</th><th>URL</th><th>Customer visibility</th></tr>
                </thead>
                <tbody>
                    @foreach ($project->links as $link)
                        <tr>
                            <td>{{ $link->label }}</td>
                            <td>{{ $link->url }}</td>
                            <td>{{ $link->visible_to_customer ? 'Visible to customer' : 'Staff only' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if (in_array('photos', $sections, true))
        <h2>Project photos and references</h2>
        @forelse ($projectPhotos as $photo)
            <div style="page-break-inside: avoid; margin: 12pt 0;">
                <img src="{{ $photo['src'] }}" alt="{{ $photo['caption'] }}" style="max-width: 100%; max-height: 220mm;">
                <p class="muted">{{ $photo['caption'] }}</p>
            </div>
        @empty
            <p class="muted">No project photos have been uploaded.</p>
        @endforelse
    @endif

    @if (in_array('requirements', $sections, true))
        <h2>Project requirements</h2>
        @if ($project->requirements_notes)
            <p style="white-space: pre-wrap">{{ $project->requirements_notes }}</p>
        @endif
        @forelse ($project->requirements as $requirement)
            <p>[{{ $requirement->is_completed ? 'Complete' : 'Incomplete' }}] {{ $requirement->requirement }}</p>
        @empty
            <p class="muted">No requirements have been recorded for this project.</p>
        @endforelse
    @endif

    @if (in_array('customers', $sections, true))
        <h2>Customers</h2>
        @php
            $linkedCustomers = $project->customers->unique(
                fn ($customer) => strtolower(trim((string) $customer->email)) ?: 'record:'.$customer->id
            );
            $accountEmail = strtolower(trim((string) $project->customerAccount?->email));
            $accountHasLinkedRecord = $accountEmail !== '' && $linkedCustomers->contains(
                fn ($customer) => strtolower(trim((string) $customer->email)) === $accountEmail
            );
        @endphp
        @if ($project->customerAccount && !$accountHasLinkedRecord)
            <p><strong>{{ $project->customerAccount->name }}</strong> · {{ $project->customerAccount->email }} (customer account)</p>
        @endif
        @forelse ($linkedCustomers as $customer)
            <p><strong>{{ $customer->name }}</strong>
                @if ($customer->email) · {{ $customer->email }}@endif
                @if ($customer->phone) · {{ $customer->phone }}@endif
            </p>
        @empty
            @unless ($project->customerAccount)
                <p class="muted">No customers are linked to this project.</p>
            @endunless
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
        </div>
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
        @php
            $hasEarlierGanttSections = collect([
                'overview',
                'requirements',
                'customers',
                'technology_stack',
                'estimates',
                'schedule',
            ])->contains(fn (string $section): bool => in_array($section, $sections, true));
        @endphp
        @if ($hasEarlierGanttSections)
            <pagebreak />
        @endif
        <div class="gantt-section">
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
                <div class="gantt-chart-page" style="{{ $loop->first ? '' : 'page-break-before: always;' }}">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr><td align="center">
                <svg class="project-gantt-chart" xmlns="http://www.w3.org/2000/svg"
                    width="175mm" height="{{ number_format($ganttPageHeightMm, 2, '.', '') }}mm"
                    viewBox="0 0 {{ $ganttViewWidth }} {{ $ganttPageHeight }}">
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
                    </td></tr>
                </table>
                </div>
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
        @php
            $hasEarlierSections = collect([
                'overview',
                'requirements',
                'customers',
                'technology_stack',
                'estimates',
                'schedule',
                'gantt',
            ])->contains(fn (string $section): bool => in_array($section, $sections, true));
        @endphp
        @if ($flowCharts->isEmpty())
            <h2>Flow charts and SOPs</h2>
        @endif
        @forelse ($flowCharts as $flowChart)
            @if (! $loop->first || $hasEarlierSections)
                <pagebreak />
            @endif
            @php
                $diagramSteps = collect($flowChart->steps ?? []);
            @endphp
            @if ($diagramSteps->isEmpty())
                <div class="flow-chart-page">
                    <h2>{{ $flowChart->title }}</h2>
                    <p class="muted">{{ $flowChart->categoryLabel() }}@if ($flowChart->subject_name) · {{ $flowChart->subject_name }}@endif</p>
                    @if ($flowChart->description)<p>{{ $flowChart->description }}</p>@endif
                    <p class="muted">No steps are recorded for this flow chart.</p>
                </div>
            @else
                @foreach ($flowChartImages[$flowChart->id]['pages'] as $imagePage)
                    @if (! $loop->first)
                        <pagebreak />
                    @endif
                    <div class="flow-chart-page">
                        <h2>{{ $flowChart->title }}@if (! $loop->first) (continued)@endif</h2>
                        @if ($loop->first)
                            <p class="muted">{{ $flowChart->categoryLabel() }}@if ($flowChart->subject_name) · {{ $flowChart->subject_name }}@endif</p>
                            @if ($flowChart->description)<p>{{ $flowChart->description }}</p>@endif
                        @endif
                        <div align="center">
                            <img class="flow-chart-diagram" src="{{ $imagePage['src'] }}"
                                alt="Flow chart: {{ $flowChart->title }}"
                                style="width: {{ $imagePage['width'] }}mm; height: {{ $imagePage['height'] }}mm;">
                        </div>
                    </div>
                @endforeach
            @endif
        @empty
            <p class="muted">No selected flow charts are linked to this project.</p>
        @endforelse
    @endif

</body>
</html>
