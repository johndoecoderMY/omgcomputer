@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="container-fluid py-3 project-show">
    <x-page-header eyebrow="Project" :title="$project->name"
        :description="$project->description"
        :back-url="route('projects.index')" back-label="All projects">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('projects.calendar') }}">
                <i class="fas fa-calendar-days me-2"></i>Calendar
            </a>
            <a class="btn btn-outline-primary" href="#project-schedule">
                <i class="fas fa-calendar-plus me-2"></i>Schedule event
            </a>
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

    <div class="card project-overview mb-4 p-2">        <div class="card-body">
            <div class="row align-items-center g-4">
                <div class="col-xl-5">
                    <div class="d-flex justify-content-between align-items-end mb-3">
                        <div>
                            <div class="small fw-semibold text-body-secondary text-uppercase mb-2">Project progress</div>
                            <div class="project-progress-value">{{ $project->progress }}%</div>
                        </div>
                        <span class="small text-body-secondary mb-1">{{ $project->completed_tasks_count }} of {{ $project->tasks_count }} complete</span>
                    </div>
                    <div class="progress project-progress-bar" role="progressbar" aria-label="Project progress"
                        aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $project->progress }}%"></div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="row g-3">
                        <div class="col-12 col-sm-3">
                            <div class="project-stat">
                                <span class="small text-body-secondary d-block mb-2">Tasks</span>
                                <span class="project-stat-value">{{ $project->tasks_count }}</span>
                                <span class="small text-body-secondary">{{ $project->completed_tasks_count }} complete</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-3">
                            <div class="project-stat">
                                <span class="small text-body-secondary d-block mb-2">Customers</span>
                                <span class="project-stat-value">{{ $project->customers->count() }}</span>
                                <span class="small text-body-secondary">linked to project</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-3">
                            <div class="project-stat">
                                <span class="small text-body-secondary d-block mb-2">Upcoming</span>
                                <span class="project-stat-value">{{ $project->events->count() }}</span>
                                <span class="small text-body-secondary">scheduled events</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-3">
                            <div class="project-stat">
                                <span class="small text-body-secondary d-block mb-2">Estimated total</span>
                                <span class="project-stat-value">RM {{ number_format($partsEstimateTotal + $otherEstimateTotal, 2) }}</span>
                                <span class="small text-body-secondary">parts + software + other</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-schedule">le">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Project schedule</h2>
                <p class="small text-body-secondary mb-0">See this project’s calendar and upcoming sessions.</p>
            </div>
            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-event-modal">
                <i class="fas fa-calendar-plus me-1"></i>Add event
            </button>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-xl-7">
                    <div id="project-mini-calendar" aria-label="{{ $project->name }} event calendar"></div>
                </div>
                <div class="col-xl-5">
                    <h3 class="h6 mb-3">Upcoming events <span class="badge text-bg-light border ms-1">{{ $project->events->count() }}</span></h3>
                    <div class="project-event-list">
                    @forelse ($project->events as $event)
                        <article class="project-event border-start border-3 ps-3 py-2 mb-3" style="--event-color: {{ \App\Models\Event::COLORS[$event->event_type] ?? \App\Models\Event::COLORS['other'] }};">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h4 class="h6 mb-1">{{ $event->title }}</h4>
                                    <div class="small text-body-secondary">
                                        {{ \App\Models\Event::TYPES[$event->event_type] ?? 'Event' }}
                                        · {{ $event->starts_at->format('D, M j · g:i A') }}
                                        @if ($event->ends_at)–{{ $event->ends_at->format('g:i A') }}@endif
                                    </div>
                                    @if ($event->location)<div class="small text-body-secondary">{{ $event->location }}</div>@endif
                                    @if ($event->description)<p class="small mb-0 mt-1">{{ $event->description }}</p>@endif
                                </div>
                                <form method="POST" action="{{ route('events.destroy', $event) }}"
                                    onsubmit="return confirm('Remove this event?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger" type="submit" aria-label="Remove {{ $event->title }}">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="small text-body-secondary project-empty-state p-3">No upcoming events. Select a date in the mini calendar or add an event.</p>
                    @endforelse
                    </div>
                    <a class="btn btn-sm btn-link px-0" href="{{ route('projects.calendar') }}">Open full calendar</a>
                </div>
            </div>
        </div>
    </div>
    @include('projects._event-modal', ['fixedProject' => $project])

    <div class="card project-panel mb-4">        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0">Flow charts &amp; SOPs</h2>
            <a class="btn btn-sm btn-primary" href="{{ route('development.flowcharts.create', ['project_id' => $project->id]) }}">
                <i class="fas fa-plus me-1"></i>Create for this project
            </a>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary">Search the flow-chart and SOP library, then link a chart or make a project-specific copy.</p>
            <form id="project-flow-chart-actions" method="POST" action="{{ route('projects.flowcharts.link', $project) }}">
                @csrf
                <div class="row g-2 align-items-end mb-4">
                    <div class="col-12 col-lg-8">
                        <label class="form-label" for="project-flow-chart-select">Find flow charts and SOP examples</label>
                        <select class="form-select js-project-search @error('flow_chart_ids') is-invalid @enderror"
                            id="project-flow-chart-select" name="flow_chart_ids[]" multiple>
                            @foreach ($availableFlowCharts as $flowChart)
                                <option value="{{ $flowChart->id }}" @selected(in_array((string) $flowChart->id, old('flow_chart_ids', []), true))>
                                    {{ ['website' => 'Website', 'process' => 'Process', 'product' => 'Product SOP', 'service' => 'Service SOP', 'education' => 'Student FYP SOP'][$flowChart->category] ?? 'Flow chart' }}
                                    · {{ $flowChart->title }}{{ $flowChart->subject_name ? ' · '.$flowChart->subject_name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('flow_chart_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('flow_chart_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-4 d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-link me-1"></i>Link selected
                        </button>
                        <button class="btn btn-sm btn-outline-primary" type="submit"
                            formaction="{{ route('projects.flowcharts.duplicate', $project) }}">
                            <i class="fas fa-copy me-1"></i>Duplicate selected for project
                        </button>
                    </div>
                </div>
            </form>
            <h3 class="h6 mb-3">Linked flow charts and SOPs</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-datatable
                    data-source="{{ route('projects.flowcharts.datatable', $project) }}"
                    data-columns="{{ json_encode([
                        ['data' => 'title', 'name' => 'flow_charts.title'],
                        ['data' => 'category_label', 'orderable' => false, 'searchable' => false],
                        ['data' => 'subject_name', 'name' => 'flow_charts.subject_name'],
                        ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                    ]) }}">
                    <thead><tr><th>Flow chart</th><th>Type</th><th>Programme or subject</th><th>Actions</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-hardware">re">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Parts &amp; hardware recommendations</h2>
                <p class="small text-body-secondary mb-0">Track suggested parts for this project without changing inventory stock.</p>
            </div>
            <span class="badge text-bg-light border">{{ $project->inventoryParts->count() }} recommended</span>
        </div>
        <div class="card-body border-bottom">
            @if ($availableParts->isNotEmpty())
                <form class="row g-3 align-items-end" method="POST" action="{{ route('projects.parts.store', $project) }}">
                    @csrf
                    <div class="col-12 col-lg-5">
                        <label class="form-label" for="project-part-search">Find a part</label>
                        <input class="form-control mb-2" id="project-part-search" type="search" placeholder="Search name or SKU">
                        <select class="form-select @error('inventory_part_id') is-invalid @enderror" id="project-part-select" name="inventory_part_id" required>
                            <option value="">Choose an inventory part</option>
                            @foreach ($availableParts as $part)
                                <option value="{{ $part->id }}" data-search="{{ strtolower($part->name.' '.$part->sku) }}"
                                    data-selling-price="{{ $part->selling_price }}"
                                    data-cost-price="{{ $part->cost_price }}"
                                    @selected(old('inventory_part_id') == $part->id)>
                                    {{ $part->name }}{{ $part->sku ? ' · '.$part->sku : '' }} · stock {{ $part->stock_quantity }}
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_part_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="project-part-quantity">Quantity needed</label>
                        <input class="form-control @error('quantity') is-invalid @enderror" id="project-part-quantity"
                            type="number" name="quantity" min="1" max="100000" value="{{ old('quantity', 1) }}" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label" for="project-part-notes">Recommendation notes</label>
                        <input class="form-control @error('notes') is-invalid @enderror" id="project-part-notes"
                            type="text" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Compatibility or intended use">
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="project-part-price-source">Price</label>
                        <select class="form-select" id="project-part-price-source" name="price_source" required>
                            <option value="selling_price" @selected(old('price_source', 'selling_price') === 'selling_price')>Selling</option>
                            <option value="cost_price" @selected(old('price_source') === 'cost_price')>Cost</option>
                            <option value="custom" @selected(old('price_source') === 'custom')>Custom</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="project-part-unit-price">Unit price (RM)</label>
                        <input class="form-control @error('unit_price') is-invalid @enderror" id="project-part-unit-price"
                            type="number" name="unit_price" min="0" max="99999999" step="0.01"
                            value="{{ old('unit_price') }}" placeholder="0.00">
                        @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-8">
                        <label class="form-label" for="project-part-url-sources">Source URLs <span class="text-body-secondary">(optional, up to 10)</span></label>
                        <div id="project-part-url-sources" class="d-grid gap-2">
                            @php($urlSources = old('url_sources', ['']))
                            @foreach ($urlSources as $index => $url)
                                <input class="form-control @error("url_sources.$index") is-invalid @enderror"
                                    type="url" name="url_sources[]" value="{{ $url }}" maxlength="2048"
                                    placeholder="https://example.com/part">
                                @error("url_sources.$index")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endforeach
                        </div>
                        @error('url_sources')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <button class="btn btn-sm btn-link px-0 mt-1" id="add-project-part-url-source" type="button">
                            <i class="fas fa-plus me-1" aria-hidden="true"></i>Add another source
                        </button>
                    </div>
                    <div class="col-12 col-lg-4">
                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-plus me-1" aria-hidden="true"></i>Add recommendation</button>
                    </div>
                </form>
            @else
                <p class="small text-body-secondary mb-2">Add parts to the inventory before recommending them for a project.</p>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.index') }}">Open inventory</a>
            @endif
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->inventoryParts as $part)
                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3">
                    <div class="flex-grow-1">
                        <a class="fw-semibold" href="{{ route('inventory.index') }}">{{ $part->name }}</a>
                        @if ($part->sku)<span class="small text-body-secondary ms-2">{{ $part->sku }}</span>@endif
                        <div class="small text-body-secondary mt-1">
                            Need {{ $part->pivot->quantity }} · In stock {{ $part->stock_quantity }}
                            · RM {{ number_format((float) ($part->pivot->unit_price ?? $part->selling_price), 2) }} each
                            · Line total RM {{ number_format($part->pivot->quantity * (float) ($part->pivot->unit_price ?? $part->selling_price), 2) }}
                            @if ($part->supplier) · Supplier: {{ $part->supplier->name }}@endif
                        </div>
                        <form class="row g-2 align-items-end mt-2" method="POST"
                            action="{{ route('projects.parts.update', [$project, $part]) }}">
                            @csrf
                            @method('PUT')
                            <div class="col-auto">
                                <label class="form-label small mb-1" for="part-price-source-{{ $part->id }}">Price source</label>
                                <select class="form-select form-select-sm project-part-price-source"
                                    id="part-price-source-{{ $part->id }}" name="price_source"
                                    data-selling-price="{{ $part->selling_price }}" data-cost-price="{{ $part->cost_price }}">
                                    <option value="selling_price" @selected(($part->pivot->price_source ?? 'selling_price') === 'selling_price')>Selling price</option>
                                    <option value="cost_price" @selected($part->pivot->price_source === 'cost_price')>Cost price</option>
                                    <option value="custom" @selected($part->pivot->price_source === 'custom')>Custom</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-1" for="part-unit-price-{{ $part->id }}">Unit price (RM)</label>
                                <input class="form-control form-control-sm project-part-unit-price" id="part-unit-price-{{ $part->id }}"
                                    type="number" name="unit_price" min="0" max="99999999" step="0.01"
                                    value="{{ number_format((float) ($part->pivot->unit_price ?? $part->selling_price), 2, '.', '') }}">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Update price</button>
                            </div>
                        </form>
                        @if ($part->pivot->notes)<p class="small mb-0 mt-1">{{ $part->pivot->notes }}</p>@endif
                        @foreach ($part->pivot->url_sources ?? [] as $sourceUrl)
                            <a class="small d-block mt-1" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">{{ $sourceUrl }}</a>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('projects.parts.destroy', [$project, $part]) }}"
                        onsubmit="return confirm('Remove this hardware recommendation?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                    </form>
                </div>
            @empty
                <div class="card-body text-body-secondary">No parts have been recommended for this project yet.</div>
            @endforelse
        </div>
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Recommended parts subtotal</span>
            <strong>RM {{ number_format($partsEstimateTotal, 2) }}</strong>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-estimate">te">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Software &amp; other project costs</h2>
                <p class="small text-body-secondary mb-0">Add software licenses, labor, or other estimated costs to the project total.</p>
            </div>
            <span class="badge text-bg-light border">{{ $project->estimateItems->count() }} items</span>
        </div>
        <div class="card-body border-bottom">
            <form class="row g-3 align-items-end" method="POST" action="{{ route('projects.estimate-items.store', $project) }}">
                @csrf
                <div class="col-12 col-md-2">
                    <label class="form-label" for="estimate-category">Cost type</label>
                    <select class="form-select" id="estimate-category" name="category" required>
                        <option value="software" @selected(old('category') === 'software')>Software</option>
                        <option value="labor" @selected(old('category') === 'labor')>Labor</option>
                        <option value="other" @selected(old('category', 'other') === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="estimate-description">Description</label>
                    <input class="form-control @error('description') is-invalid @enderror" id="estimate-description"
                        name="description" value="{{ old('description') }}" maxlength="255" required
                        placeholder="e.g. IDE subscription">
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label" for="estimate-quantity">Qty</label>
                    <input class="form-control @error('quantity') is-invalid @enderror" id="estimate-quantity"
                        type="number" name="quantity" min="1" max="100000" value="{{ old('quantity', 1) }}" required>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="estimate-unit-price">Unit price (RM)</label>
                    <input class="form-control @error('unit_price') is-invalid @enderror" id="estimate-unit-price"
                        type="number" name="unit_price" min="0" max="99999999" step="0.01"
                        value="{{ old('unit_price') }}" required>
                    @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Add cost</button>
                </div>
            </form>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->estimateItems as $item)
                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3">
                    <div>
                        <span class="badge text-bg-light border">{{ ucfirst($item->category) }}</span>
                        <span class="fw-semibold ms-2">{{ $item->description }}</span>
                        <div class="small text-body-secondary mt-1">
                            {{ $item->quantity }} × RM {{ number_format((float) $item->unit_price, 2) }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <strong>RM {{ number_format($item->line_total, 2) }}</strong>
                        <form method="POST" action="{{ route('projects.estimate-items.destroy', [$project, $item]) }}"
                            onsubmit="return confirm('Remove this project cost?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card-body text-body-secondary">No software or other project costs have been added.</div>
            @endforelse
        </div>
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Software &amp; other subtotal</span>
            <strong>RM {{ number_format($otherEstimateTotal, 2) }}</strong>
        </div>
    </div>

    <div class="card project-panel mb-4">        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Customer linked to this project</h2>
            <span class="badge text-bg-light border">{{ $project->customers->count() }}</span>
        </div>
        @forelse ($project->customers as $customer)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-2 px-4 py-3">
                <div><a class="fw-semibold" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a><div class="small text-body-secondary">{{ $customer->email ?: $customer->phone }}</div></div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('customers.show', $customer) }}">View profile</a>
            </div>
        @empty
            <div class="card-body text-body-secondary">No customer is linked to this project. You can change this from Edit project.</div>
        @endforelse
    </div>

    <div class="card project-panel">        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0">Tasks</h2>
            <span class="badge text-bg-light border">{{ $project->tasks->count() }}</span>
        </div>
        @if ($project->tasks->isNotEmpty())
            <div class="list-group list-group-flush">
                @foreach ($project->tasks as $task)
                    <article class="list-group-item project-task">
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
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const miniCalendarElement = document.getElementById('project-mini-calendar');
        if (miniCalendarElement && window.FullCalendar) {
            const projectCalendar = new FullCalendar.Calendar(miniCalendarElement, {
                initialView: 'dayGridMonth',
                height: 'auto',
                fixedWeekCount: false,
                headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
                dayMaxEvents: true,
                events: {
                    url: @json(route('projects.calendar.events')),
                    extraParams: { project_id: @json($project->id) },
                },
                dateClick: (info) => {
                    document.getElementById('calendar-event-start').value = `${info.dateStr.slice(0, 10)}T09:00`;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('create-event-modal')).show();
                },
                eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
            });
            projectCalendar.render();
        }

        const partUrlSources = document.getElementById('project-part-url-sources');
        document.getElementById('add-project-part-url-source')?.addEventListener('click', () => {
            if (!partUrlSources || partUrlSources.querySelectorAll('input').length >= 10) return;

            const input = document.createElement('input');
            input.className = 'form-control';
            input.type = 'url';
            input.name = 'url_sources[]';
            input.maxLength = 2048;
            input.placeholder = 'https://example.com/part';
            partUrlSources.append(input);
        });

        const partSelect = document.getElementById('project-part-select');
        const newPartPriceSource = document.getElementById('project-part-price-source');
        const newPartUnitPrice = document.getElementById('project-part-unit-price');
        const setPartPrice = (source, priceInput, sellingPrice, costPrice) => {
            priceInput.readOnly = source.value !== 'custom';
            if (source.value === 'selling_price') priceInput.value = sellingPrice ?? '';
            if (source.value === 'cost_price') priceInput.value = costPrice ?? '';
        };

        const updateNewPartPrice = () => {
            const option = partSelect?.selectedOptions[0];
            if (!option || !newPartPriceSource || !newPartUnitPrice) return;

            setPartPrice(
                newPartPriceSource,
                newPartUnitPrice,
                option.dataset.sellingPrice,
                option.dataset.costPrice,
            );
        };

        partSelect?.addEventListener('change', updateNewPartPrice);
        newPartPriceSource?.addEventListener('change', updateNewPartPrice);
        if (newPartPriceSource && newPartUnitPrice && partSelect?.value) {
            if (newPartPriceSource.value === 'custom') {
                newPartUnitPrice.readOnly = false;
            } else {
                updateNewPartPrice();
            }
        } else if (newPartUnitPrice) {
            newPartUnitPrice.readOnly = newPartPriceSource?.value !== 'custom';
        }

        document.querySelectorAll('.project-part-price-source').forEach((source) => {
            const priceInput = source.closest('form')?.querySelector('.project-part-unit-price');
            if (!priceInput) return;

            const updateExistingPartPrice = () => setPartPrice(
                source,
                priceInput,
                source.dataset.sellingPrice,
                source.dataset.costPrice,
            );
            source.addEventListener('change', updateExistingPartPrice);
            updateExistingPartPrice();
        });
    });
</script>
@endpush
