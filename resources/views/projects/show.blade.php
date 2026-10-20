@extends('layouts.app')

@section('title', $project->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/glightbox/glightbox.min.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3 project-show">
    <x-page-header eyebrow="Project" :title="$project->name"
        :description="$project->description"
        :back-url="route('projects.index')" back-label="All projects">
        <x-slot:actions>
            <button class="btn" type="button" data-bs-toggle="modal" data-bs-target="#project-export-modal">
                <i class="fas fa-file-pdf fa-fw me-2"></i>Export PDF
            </button>
            <a href="{{ route('projects.calendar') }}"><i class="fas fa-calendar-days fa-fw me-2"></i>Calendar</a>
            <a href="#project-schedule"><i class="fas fa-calendar-plus fa-fw me-2"></i>Schedule event</a>
            <a href="{{ route('projects.edit', $project) }}"><i class="fas fa-pen fa-fw me-2"></i>Edit project</a>
            <a href="{{ route('projects.tasks.create', $project) }}"><i class="fas fa-plus fa-fw me-2"></i>Add task</a>
            <a href="{{ route('customers.create', ['project_id' => $project->id]) }}"><i class="fas fa-user-plus fa-fw me-2"></i>Add customer</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="modal fade" id="project-export-modal" tabindex="-1" aria-labelledby="project-export-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('projects.export', $project) }}" target="_blank">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="project-export-title">Export project PDF</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Select the project information to include. Task details are excluded unless you select the Gantt chart.</p>
                        <div class="row g-2">
                            @foreach ([
                                'overview' => 'Project overview',
                                'photos' => 'Photos and references',
                                'requirements' => 'Requirements checklist and notes',
                                'customers' => 'Linked customers',
                                'technology_stack' => 'Technology stack and versions',
                                'estimates' => 'Software and other estimates',
                                'schedule' => 'Project event schedule',
                                'gantt' => 'Task Gantt chart',
                                'flowcharts' => 'Flow charts and SOPs',
                            ] as $section => $label)
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sections[]" value="{{ $section }}" id="export-{{ $section }}" checked>
                                        <label class="form-check-label" for="export-{{ $section }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <fieldset class="mt-4">
                            <legend class="form-label">Flow charts to include</legend>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="flowchart_mode" value="all" id="flowcharts-all" checked>
                                <label class="form-check-label" for="flowcharts-all">All linked flow charts and SOPs</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="flowchart_mode" value="selected" id="flowcharts-selected">
                                <label class="form-check-label" for="flowcharts-selected">Only selected flow charts</label>
                            </div>
                            <label class="form-label mt-2" for="export-flowchart-ids">Choose flow charts</label>
                            <select class="form-select" name="flow_chart_ids[]" id="export-flowchart-ids" multiple size="5">
                                @foreach ($project->flowCharts as $flowChart)
                                    <option value="{{ $flowChart->id }}">{{ $flowChart->title }} · {{ $flowChart->category }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Used only when “Only selected flow charts” is chosen. Use Ctrl or Cmd to select multiple charts.</div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-file-pdf me-2"></i>Generate PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    @include('projects._links', ['project' => $project])

    <section class="card project-panel mb-4" aria-labelledby="project-photos-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0" id="project-photos-heading">Project photos and references</h2>
            <p class="small text-body-secondary mb-0">Upload project images, open them in a lightbox, or include them in the project PDF.</p>
        </div>
        <form class="card-body border-bottom" method="POST" action="{{ route('projects.attachments.store', $project) }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="project-photos">Photos</label>
                    <input class="form-control" id="project-photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="project-photo-caption">Caption</label>
                    <input class="form-control" id="project-photo-caption" name="caption" maxlength="255" placeholder="What should the team notice?">
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Upload photos</button></div>
            </div>
            <div class="form-text">Upload up to 8 JPG, PNG, or WebP images, up to 10 MB each.</div>
        </form>
        <div class="card-body">
            @include('attachments.gallery', [
                'attachments' => $project->attachments,
                'emptyMessage' => 'No project photos yet.',
                'lightbox' => true,
                'editableCaptions' => true,
                'galleryName' => 'project-photos',
                'captionRoute' => 'projects.attachments.caption',
                'captionRouteParameters' => [$project],
            ])
        </div>
    </section>

    <section class="card project-panel mb-4" aria-labelledby="project-requirements-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0" id="project-requirements-heading">Project requirements</h2>
                <p class="small text-body-secondary mb-0">Keep the project scope and requirements in one place.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.edit', $project) }}">
                <i class="fas fa-pen me-1" aria-hidden="true"></i>Edit requirements
            </a>
        </div>
        <div class="card-body">
            @if ($project->requirements_notes)
                <p class="mb-4" style="white-space: pre-line">{{ $project->requirements_notes }}</p>
            @endif
            <h3 class="h6">Checklist</h3>
            @forelse ($project->requirements as $requirement)
                <div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <i class="fas {{ $requirement->is_completed ? 'fa-check-square text-success' : 'fa-square text-body-secondary' }} mt-1"
                        aria-hidden="true"></i>
                    <span @class(['text-decoration-line-through text-body-secondary' => $requirement->is_completed])>
                        {{ $requirement->requirement }}
                    </span>
                    <span class="visually-hidden">{{ $requirement->is_completed ? 'Complete' : 'Incomplete' }}</span>
                </div>
            @empty
                <p class="text-body-secondary mb-0">No requirements have been added yet.</p>
            @endforelse
        </div>
    </section>

    <div class="card project-panel mb-4" id="project-schedule">
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
            <p class="small text-body-secondary">Link complete custom flow charts. Example flow charts and SOPs must be duplicated before they can be used by a project.</p>
            <form id="project-flow-chart-actions" method="POST" action="{{ route('projects.flowcharts.link', $project) }}">
                @csrf
                <div class="row g-2 align-items-end mb-4">
                    <div class="col-12 col-lg-8">
                        <label class="form-label" for="project-flow-chart-select">Find custom flow charts to link</label>
                        <select class="form-select js-project-search @error('flow_chart_ids') is-invalid @enderror"
                            id="project-flow-chart-select" name="flow_chart_ids[]" multiple>
                            @foreach ($availableFlowCharts as $flowChart)
                                <option value="{{ $flowChart->id }}" @selected(in_array((string) $flowChart->id, old('flow_chart_ids', []), true))>
                                    {{ ['website' => 'Website', 'process' => 'Process', 'product' => 'Product SOP', 'service' => 'Service SOP', 'education' => 'Student FYP SOP'][$flowChart->category] ?? 'Flow chart' }}
                                    · {{ $flowChart->title }}{{ $flowChart->subject_name ? ' · '.$flowChart->subject_name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if ($availableFlowCharts->isEmpty())<div class="form-text">No unlinked custom flow charts are available.</div>@endif
                        @error('flow_chart_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('flow_chart_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-link me-1"></i>Link selected
                        </button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('projects.flowcharts.duplicate', $project) }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-12 col-lg-8">
                    <label class="form-label" for="project-example-flow-chart-select">Find SOP examples to duplicate</label>
                    <select class="form-select js-project-search @error('flow_chart_ids') is-invalid @enderror"
                        id="project-example-flow-chart-select" name="flow_chart_ids[]" multiple>
                        @foreach ($availableExamples as $flowChart)
                            <option value="{{ $flowChart->id }}" @selected(in_array((string) $flowChart->id, old('flow_chart_ids', []), true))>
                                {{ ['website' => 'Website', 'process' => 'Process', 'product' => 'Product SOP', 'service' => 'Service SOP', 'education' => 'Student FYP SOP'][$flowChart->category] ?? 'Flow chart' }}
                                · {{ $flowChart->title }}{{ $flowChart->subject_name ? ' · '.$flowChart->subject_name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($availableExamples->isEmpty())<div class="form-text">No example charts are available to duplicate.</div>@endif
                    @error('flow_chart_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('flow_chart_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-lg-4">
                    <button class="btn btn-sm btn-outline-primary" type="submit">
                        <i class="fas fa-copy me-1"></i>Duplicate selected for project
                    </button>
                </div>
            </form>
            <h3 class="h6 mb-3">Linked flow charts and SOPs</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
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

    <div class="card project-panel mb-4" id="project-technology-stack">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Project technology stack</h2>
                <p class="small text-body-secondary mb-0">Record the technologies and versions used by this project, such as PHP, Laravel, and FullCalendar.</p>
            </div>
            <span class="badge text-bg-light border">{{ $project->technologyStack->count() }} technologies</span>
        </div>
        <div class="card-body border-bottom">
            <form class="row g-3 align-items-end" method="POST" action="{{ route('projects.technology-stack.store', $project) }}">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label" for="technology-name">Technology</label>
                    <input class="form-control" id="technology-name" name="name" value="{{ old('name') }}" maxlength="120" placeholder="e.g. PHP" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="technology-version">Version</label>
                    <input class="form-control" id="technology-version" name="version" value="{{ old('version') }}" maxlength="80" placeholder="e.g. 8.3">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="technology-notes">Notes <span class="text-body-secondary">(optional)</span></label>
                    <input class="form-control" id="technology-notes" name="notes" value="{{ old('notes') }}" maxlength="1000" placeholder="e.g. Frontend calendar component">
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Add technology</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead><tr><th scope="col">Technology</th><th scope="col">Version</th><th scope="col">Notes</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse ($project->technologyStack as $technology)
                        <tr>
                            <td><input class="form-control" form="technology-update-{{ $technology->id }}" name="name" value="{{ $technology->name }}" maxlength="120" required aria-label="Technology name"></td>
                            <td><input class="form-control" form="technology-update-{{ $technology->id }}" name="version" value="{{ $technology->version }}" maxlength="80" aria-label="Technology version"></td>
                            <td><input class="form-control" form="technology-update-{{ $technology->id }}" name="notes" value="{{ $technology->notes }}" maxlength="1000" aria-label="Technology notes"></td>
                            <td class="text-nowrap">
                                <form id="technology-update-{{ $technology->id }}" class="d-inline" method="POST" action="{{ route('projects.technology-stack.update', [$project, $technology]) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                </form>
                                <form class="d-inline" method="POST" action="{{ route('projects.technology-stack.destroy', [$project, $technology]) }}"
                                    onsubmit="return confirm('Remove this technology from the project?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-body-secondary" colspan="4">No project technologies have been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-hardware">
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
                            @php
                                $urlSources = old('url_sources', ['']);
                            @endphp
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
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr><th scope="col">Part</th><th scope="col">Needed / stock</th><th scope="col">Unit price</th><th scope="col">Notes and sources</th><th scope="col">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($project->inventoryParts as $part)
                        @php
                            $recommendedUnitPrice = (float) ($part->pivot->unit_price ?? $part->selling_price);
                        @endphp
                        <tr>
                            <th scope="row">
                                <a href="{{ route('inventory.index') }}">{{ $part->name }}</a>
                                @if ($part->sku)<div class="small text-body-secondary">{{ $part->sku }}</div>@endif
                                @if ($part->supplier)<div class="small text-body-secondary">Supplier: {{ $part->supplier->name }}</div>@endif
                            </th>
                            <td>{{ $part->pivot->quantity }} needed<br><span class="small text-body-secondary">{{ $part->stock_quantity }} in stock</span></td>
                            <td>
                                <div>RM {{ number_format($recommendedUnitPrice, 2) }}</div>
                                <form class="d-grid gap-1 mt-2" method="POST" action="{{ route('projects.parts.update', [$project, $part]) }}">
                                    @csrf
                                    @method('PUT')
                                    <label class="visually-hidden" for="part-price-source-{{ $part->id }}">Price source</label>
                                    <select class="form-select form-select-sm project-part-price-source"
                                        id="part-price-source-{{ $part->id }}" name="price_source"
                                        data-selling-price="{{ $part->selling_price }}" data-cost-price="{{ $part->cost_price }}">
                                        <option value="selling_price" @selected(($part->pivot->price_source ?? 'selling_price') === 'selling_price')>Selling price</option>
                                        <option value="cost_price" @selected($part->pivot->price_source === 'cost_price')>Cost price</option>
                                        <option value="custom" @selected($part->pivot->price_source === 'custom')>Custom</option>
                                    </select>
                                    <label class="visually-hidden" for="part-unit-price-{{ $part->id }}">Unit price (RM)</label>
                                    <input class="form-control form-control-sm project-part-unit-price" id="part-unit-price-{{ $part->id }}"
                                        type="number" name="unit_price" min="0" max="99999999" step="0.01"
                                        value="{{ number_format($recommendedUnitPrice, 2, '.', '') }}">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Update price</button>
                                </form>
                            </td>
                            <td>
                                @if ($part->pivot->notes)<div>{{ $part->pivot->notes }}</div>@endif
                                @foreach ($part->pivot->url_sources ?? [] as $sourceUrl)
                                    <a class="d-block small" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">{{ $sourceUrl }}</a>
                                @endforeach
                                @if (! $part->pivot->notes && empty($part->pivot->url_sources))
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-body-secondary mb-2">Line total<br><strong class="text-body">RM {{ number_format($part->pivot->quantity * $recommendedUnitPrice, 2) }}</strong></div>
                                <form method="POST" action="{{ route('projects.parts.destroy', [$project, $part]) }}"
                                    onsubmit="return confirm('Remove this hardware recommendation?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-body-secondary" colspan="5">No parts have been recommended for this project yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Recommended parts subtotal</span>
            <strong>RM {{ number_format($partsEstimateTotal, 2) }}</strong>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-estimate">        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
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

    <div class="card project-panel mb-4">
        <div class="card-header bg-body fw-semibold">Assigned people</div>
        <div class="card-body">
            @if ($project->assignees->isNotEmpty())
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($project->assignees as $assignee)
                        <span class="badge text-bg-light border">{{ $assignee->name }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-body-secondary mb-0">No people assigned to this project.</p>
            @endif
        </div>
    </div>

    @php
        $ganttTasks = $project->tasks->flatMap(fn ($task) => collect([$task])->concat($task->subtasks))->values();
        $datedGanttTasks = $ganttTasks->filter(fn ($task) => $task->start_date || $task->due_date);
        $ganttStart = $datedGanttTasks
            ->map(fn ($task) => $task->start_date ?? $task->due_date)
            ->sortBy(fn ($date) => $date->timestamp)
            ->first();
        $ganttEnd = $datedGanttTasks
            ->map(fn ($task) => $task->due_date ?? $task->start_date)
            ->sortByDesc(fn ($date) => $date->timestamp)
            ->first();
        $ganttDays = $ganttStart && $ganttEnd ? max(1, (int) $ganttStart->diffInDays($ganttEnd) + 1) : 0;
    @endphp
    <div class="card project-panel mb-4" id="project-gantt">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Task Gantt chart</h2>
                <p class="small text-body-secondary mb-0">Planned task start and due dates; tasks without dates are listed as unscheduled.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.tasks.create', $project) }}">Add scheduled task</a>
        </div>
        <div class="card-body">
            @if ($ganttTasks->isEmpty())
                <p class="text-body-secondary mb-0">Add tasks to display the project Gantt chart.</p>
            @else
                @if ($ganttStart && $ganttEnd)
                    <div class="d-flex justify-content-between small text-body-secondary mb-2">
                        <span>{{ $ganttStart->format('M j, Y') }}</span>
                        <span>{{ $ganttEnd->format('M j, Y') }}</span>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr><th scope="col">Task</th><th scope="col">Status</th><th scope="col">Schedule</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($ganttTasks as $ganttTask)
                                @php
                                    $barStart = $ganttTask->start_date ?? $ganttTask->due_date;
                                    $barEnd = $ganttTask->due_date ?? $ganttTask->start_date;
                                    $barOffset = $barStart && $ganttStart ? (int) $ganttStart->diffInDays($barStart) : 0;
                                    $barDuration = $barStart && $barEnd ? (int) $barStart->diffInDays($barEnd) + 1 : 0;
                                    $barWidth = $ganttDays > 0 ? max(1, $barDuration / $ganttDays * 100) : 0;
                                    $barLeft = $ganttDays > 0 ? $barOffset / $ganttDays * 100 : 0;
                                @endphp
                                <tr>
                                    <th scope="row">
                                        @if ($ganttTask->parent_task_id)<span class="text-body-secondary me-1">↳</span>@endif
                                        <a href="{{ route('tasks.show', $ganttTask) }}">{{ $ganttTask->title }}</a>
                                        <div class="small text-body-secondary">{{ \App\Models\Task::PRIORITIES[$ganttTask->priority] }}</div>
                                    </th>
                                    <td><x-status-badge :status="$ganttTask->status" :label="\App\Models\Task::STATUSES[$ganttTask->status] ?? $ganttTask->status" /></td>
                                    <td style="min-width: 240px;">
                                        @if ($barStart && $barEnd && $ganttDays > 0)
                                            <div class="position-relative bg-body-tertiary rounded" style="height: 2rem;">
                                                <div class="position-absolute top-0 h-100 rounded bg-{{ \App\Support\StatusBadge::colorClass($ganttTask->status) }}"
                                                    style="left: {{ number_format($barLeft, 3, '.', '') }}%; width: {{ number_format(min($barWidth, 100 - $barLeft), 3, '.', '') }}%;"
                                                    title="{{ $barStart->format('M j, Y') }} – {{ $barEnd->format('M j, Y') }}">
                                                </div>
                                            </div>
                                            <span class="small text-body-secondary">{{ $barStart->format('M j') }}–{{ $barEnd->format('M j, Y') }}</span>
                                        @else
                                            <span class="small text-body-secondary">Unscheduled</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
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
                                    <x-status-badge :status="$task->status" :label="\App\Models\Task::STATUSES[$task->status] ?? $task->status" />
                                    <span class="badge text-bg-light border">{{ \App\Models\Task::PRIORITIES[$task->priority] }} priority</span>
                                    @if ($task->due_date)
                                        <span class="small text-body-secondary">Due {{ $task->due_date->format('M j, Y') }}</span>
                                    @endif
                                    @if ($task->assignees->isNotEmpty())
                                        <span class="small text-body-secondary">Assigned to {{ $task->assignees->pluck('name')->join(', ') }}</span>
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
                                            <x-status-badge :status="$subtask->status" :label="\App\Models\Task::STATUSES[$subtask->status] ?? $subtask->status" />
                                            @if ($subtask->assignees->isNotEmpty())
                                                <span class="small text-body-secondary">{{ $subtask->assignees->pluck('name')->join(', ') }}</span>
                                            @endif
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
    <script src="{{ asset('vendors/glightbox/glightbox.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.GLightbox) {
                window.GLightbox({ selector: '.attachment-lightbox' });
            }
        });
    </script>
@endpush

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
                eventDidMount: (info) => {
                    const backgroundColor = info.event.backgroundColor;
                    const textColor = info.event.textColor;
                    if (backgroundColor) {
                        info.el.style.setProperty('background-color', backgroundColor, 'important');
                        info.el.style.setProperty('border-color', backgroundColor, 'important');
                    }
                    info.el.style.setProperty('opacity', '1', 'important');
                    if (textColor) {
                        info.el.style.setProperty('color', textColor, 'important');
                        info.el.querySelector('.fc-event-main')?.style.setProperty('color', textColor, 'important');
                        info.el.querySelectorAll('.fc-event-title, .fc-event-time').forEach((element) => {
                            element.style.setProperty('color', textColor, 'important');
                        });
                    }
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
