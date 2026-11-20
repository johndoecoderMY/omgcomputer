@extends('layouts.app')

@section('title', $project->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/glightbox/glightbox.min.css') }}">
    <style>
        .project-logo-image,
        .project-logo-placeholder {
            width: 5.5rem;
            height: 5.5rem;
            border: 1px solid var(--falcon-border-color, var(--bs-border-color));
            border-radius: .75rem;
            background: var(--falcon-tertiary-bg, var(--bs-tertiary-bg));
        }

        .project-logo-image {
            object-fit: contain;
            padding: .5rem;
        }

        .project-logo-placeholder {
            display: grid;
            place-items: center;
            color: var(--bs-secondary-color);
            font-size: 1.75rem;
        }

        .project-photo-dropzone {
            border: 1px dashed var(--falcon-border-color, var(--bs-border-color));
            border-radius: .75rem;
            padding: clamp(1.25rem, 4vw, 2rem);
            text-align: center;
            background: var(--falcon-tertiary-bg, var(--bs-tertiary-bg));
            transition: border-color .15s ease, background-color .15s ease;
        }

        .project-photo-dropzone.is-dragging {
            border-color: var(--bs-primary);
            background: var(--bs-primary-bg-subtle);
        }

        .project-photo-dropzone-icon {
            display: block;
            font-size: 2.5rem;
            line-height: 1;
            margin-bottom: .75rem;
        }

        .project-photo-dropzone [data-image-upload] {
            display: inline-block !important;
            width: auto;
            vertical-align: middle;
        }

        .project-photo-dropzone [data-image-upload] .input-group {
            display: inline-flex;
            width: auto;
            margin-top: .75rem;
        }

        .project-photo-dropzone [data-image-upload-selection] {
            display: none;
        }

        .project-photo-paste {
            max-width: 28rem;
            resize: vertical;
            text-align: center;
        }

        .project-photo-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(9rem, 12rem));
            gap: .75rem;
        }

        .project-photo-preview-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--falcon-border-color, var(--bs-border-color));
            border-radius: .5rem;
            background: var(--falcon-tertiary-bg, var(--bs-tertiary-bg));
        }

        .project-photo-upload-preview {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: contain;
            background: var(--bs-dark);
        }

        .project-photo-preview-details {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .5rem;
        }

        .project-photo-image-button {
            display: block;
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .project-photo-image-button:focus-visible {
            outline: 2px solid var(--bs-primary);
            outline-offset: -2px;
        }

        .project-photo-caption-button {
            margin: 0 .5rem .5rem;
            width: calc(100% - 1rem);
            white-space: normal;
            overflow-wrap: anywhere;
            text-align: left;
        }

        .project-photo-caption-modal-image {
            display: block;
            width: 100%;
            max-height: min(55vh, 32rem);
            object-fit: contain;
            border-radius: .5rem;
            background: var(--bs-dark);
        }
    </style>
@endpush

@section('content')
@php
    $linkedCustomers = $project->customers->unique(
        fn ($customer) => strtolower(trim((string) $customer->email)) ?: 'record:'.$customer->id
    );
    $accountEmail = strtolower(trim((string) $project->customerAccount?->email));
    $accountHasLinkedRecord = $accountEmail !== '' && $linkedCustomers->contains(
        fn ($customer) => strtolower(trim((string) $customer->email)) === $accountEmail
    );
    $linkedCustomerCount = $linkedCustomers->count() + ($project->customerAccount && !$accountHasLinkedRecord ? 1 : 0);
@endphp
<div class="container-fluid py-3 project-show" data-project-ajax-root data-project-id="{{ $project->id }}">
    <x-page-header eyebrow="Project" :title="$project->name"
        :description="$project->description"
        :back-url="route('projects.index')" back-label="All projects">
        <x-slot:actions>
            <a href="{{ route('projects.edit', $project) }}" data-project-ajax-modal><i class="bi bi-pencil bi-fw me-2"></i>Edit project</a>
            <button class="btn" type="button" data-bs-toggle="modal" data-bs-target="#project-history-modal">
                <i class="bi bi-clock-history bi-fw me-2"></i>Project history
            </button>
            <button class="btn" type="button" data-bs-toggle="modal" data-bs-target="#project-export-modal">
                <i class="bi bi-filetype-pdf bi-fw me-2"></i>Export PDF
            </button>
            <a href="{{ route('projects.calendar', $project) }}"><i class="bi bi-calendar3 bi-fw me-2"></i>Project calendar</a>
            <a href="#project-schedule"><i class="bi bi-calendar-plus bi-fw me-2"></i>Schedule event</a>
            <a href="{{ route('projects.planning', $project) }}"><i class="bi bi-diagram-3 bi-fw me-2"></i>Planning workspace</a>
            <a href="{{ route('projects.tasks.create', $project) }}" data-project-ajax-modal><i class="bi bi-plus bi-fw me-2"></i>Add task</a>
            <a href="{{ route('customers.create', ['project_id' => $project->id]) }}"><i class="bi bi-person-plus bi-fw me-2"></i>Add customer</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card project-panel mb-4" aria-labelledby="project-logo-heading" data-project-logo>
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            @if ($project->logo_path)
                <img class="project-logo-image" src="{{ route('projects.logo', $project) }}" alt="{{ $project->name }} logo">
            @else
                <div class="project-logo-placeholder" aria-hidden="true">
                    <i class="bi bi-image"></i>
                </div>
            @endif
            <div class="flex-grow-1">
                <h2 class="h6 mb-1" id="project-logo-heading">Project logo</h2>
                <p class="small text-body-secondary mb-0">Shown on this project’s detail page. PNG, JPEG, or WebP up to 5 MB.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 ms-auto">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#project-logo-modal">
                    <i class="bi bi-upload me-2" aria-hidden="true"></i>{{ $project->logo_path ? 'Replace logo' : 'Add logo' }}
                </button>
                @if ($project->logo_path)
                    <form method="POST" action="{{ route('projects.logo.destroy', $project) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">Remove logo</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="modal fade" id="project-logo-modal" tabindex="-1" aria-labelledby="project-logo-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('projects.logo.store', $project) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5" id="project-logo-title">{{ $project->logo_path ? 'Replace project logo' : 'Add project logo' }}</h2>
                            <p class="small text-body-secondary mb-0">{{ $project->name }}</p>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="project-logo-file">Logo image</label>
                        <input class="form-control" id="project-logo-file" type="file" name="logo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <div class="form-text">Choose a PNG, JPEG, or WebP image up to 5 MB.</div>
                        @error('logo')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Save logo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="project-history-modal" tabindex="-1" aria-labelledby="project-history-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="project-history-title">Project history</h2>
                        <p class="small text-body-secondary mb-0">Project, task, planning, schedule, link, photo, estimate, customer, and team updates.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    @forelse ($projectActivities as $activity)
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex align-items-start gap-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-2">
                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="fw-semibold">{{ $activity->summary }}</div>
                                    @if (!empty($activity->details))
                                        <div class="small text-body-secondary">{{ implode(' · ', $activity->details) }}</div>
                                    @endif
                                    <div class="small text-body-secondary">
                                        {{ $activity->user?->name ?? 'Former user' }}
                                        <span aria-hidden="true">·</span>
                                        <time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('M j, Y · g:i A') }}</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-body-secondary">
                            No activity has been recorded for this project yet.
                        </div>
                    @endforelse
                </div>
                <div class="modal-footer">
                    <span class="small text-body-secondary me-auto">{{ $projectActivities->count() }} recent {{ \Illuminate\Support\Str::plural('update', $projectActivities->count()) }}</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
                        <p>Select the project information to include. Task details are excluded unless you select the project roadmap.</p>
                        <div class="row g-2">
                            @foreach ([
                                'overview' => 'Project overview',
                                'planning' => 'Project brief, modules, roadmap, tests and issues',
                                'links' => 'Project links',
                                'photos' => 'Photos and references',
                                'requirements' => 'Requirements checklist and notes',
                                'customers' => 'Linked customers',
                                'technology_stack' => 'Technology stack and versions',
                                'estimates' => 'Software and other estimates',
                                'schedule' => 'Project event schedule',
                                'gantt' => 'Project roadmap',
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
                                    <option value="{{ $flowChart->id }}"
                                        @selected(old('project_flowcharts_action') === 'link'
                                            && in_array((string) $flowChart->id, old('flow_chart_ids', []), true))>
                                        {{ $flowChart->title }} · {{ $flowChart->categoryLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Used only when “Only selected flow charts” is chosen. Use Ctrl or Cmd to select multiple charts.</div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-filetype-pdf me-2"></i>Generate PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card project-overview mb-4 p-2">
        <div class="card-body">
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
                                <span class="project-stat-value">{{ $linkedCustomerCount }}</span>
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
                                <span class="project-stat-value">RM {{ number_format($partsEstimateTotal + $customHardwareTotal + $otherEstimateTotal, 2) }}</span>
                                <span class="small text-body-secondary">parts + software + other</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('projects._links', ['project' => $project])

    @include('projects._billing-documents', ['project' => $project, 'billingDocuments' => $billingDocuments, 'canManageBilling' => $canManageBilling])

    <div class="card project-panel mb-4" aria-labelledby="project-photos-heading" data-project-photos>
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0" id="project-photos-heading">Project photos and references</h2>
                <p class="small text-body-secondary mb-0">Add photos with individual captions, or include them in the project PDF.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#project-photo-upload-modal">
                <span class="bi bi-camera me-2" aria-hidden="true"></span>Add photos
            </button>
        </div>
        <div class="card-body">
            @include('attachments.gallery', [
                'attachments' => $project->attachments,
                'emptyMessage' => 'No project photos yet.',
                'lightbox' => true,
                'editableCaptions' => true,
                'captionEditorModal' => true,
                'galleryName' => 'project-photos',
                'captionRoute' => 'projects.attachments.caption',
                'captionRouteParameters' => [$project],
            ])
        </div>
        <div class="modal fade" id="project-photo-upload-modal" tabindex="-1"
            aria-labelledby="project-photo-upload-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form id="project-photo-upload-form" method="POST"
                        action="{{ route('projects.attachments.store', $project) }}"
                        enctype="multipart/form-data" data-project-photo-upload-form>
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title fs-5" id="project-photo-upload-title">Add project photos</h2>
                                <div class="small text-body-secondary">{{ $project->name }}</div>
                            </div>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" role="alert" data-project-photo-error></div>
                            <div class="project-photo-dropzone mb-3" data-project-photo-dropzone>
                                <span class="bi bi-images project-photo-dropzone-icon text-body-secondary" aria-hidden="true"></span>
                                <div class="fw-semibold">Drag and drop photos here</div>
                                <div class="small text-body-secondary">or choose images, take a photo, or paste screenshots</div>
                                <label class="btn btn-primary mt-3" for="project-photo-file">
                                    <span class="bi bi-plus-lg me-1" aria-hidden="true"></span>Choose images
                                </label>
                                <x-image-upload-input id="project-photo-file" name="photos[]"
                                    :multiple="true" inputClass="visually-hidden" />
                                <textarea class="form-control project-photo-paste mt-3 mx-auto"
                                    id="project-photo-paste" data-project-photo-paste rows="1"
                                    placeholder="Or click here and press Ctrl+V or Cmd+V"
                                    aria-label="Paste photos or screenshots"></textarea>
                                <div class="form-text mt-2">JPEG, PNG, or WebP. Up to 8 photos per upload. Click each photo to add its caption.</div>
                            </div>
                            <div class="project-photo-preview-grid d-none mb-3" data-project-photo-preview aria-live="polite"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Upload photos</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="project-photo-caption-modal" tabindex="-1"
            aria-labelledby="project-photo-caption-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5" id="project-photo-caption-title">Add photo caption</h2>
                            <div class="small text-body-secondary" data-project-photo-caption-filename></div>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <img class="project-photo-caption-modal-image mb-3" data-project-photo-caption-image alt="">
                        <label class="form-label" for="project-photo-caption-input">Caption</label>
                        <input class="form-control" id="project-photo-caption-input"
                            data-project-photo-caption-input maxlength="255" autocomplete="off"
                            aria-describedby="project-photo-caption-error">
                        <div class="invalid-feedback" id="project-photo-caption-error">Enter a caption for this photo.</div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="button" data-project-photo-caption-save>Save caption</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card project-panel mb-4" aria-labelledby="project-requirements-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0" id="project-requirements-heading">Project requirements</h2>
                <p class="small text-body-secondary mb-0">Keep the project scope and requirements in one place.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.planning', $project) }}#planning-requirements-heading">
            <i class="bi bi-pencil me-1" aria-hidden="true"></i>Manage requirements
            </a>
        </div>
        <div class="card-body">
            @if ($project->requirements_notes)
                <p class="mb-4" style="white-space: pre-line">{{ $project->requirements_notes }}</p>
            @endif
            <h3 class="h6">Checklist</h3>
            @forelse ($project->requirements as $requirement)
                <div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <i class="bi {{ $requirement->is_completed ? 'bi-check-square text-success' : 'bi-square text-body-secondary' }} mt-1"
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
    </div>

    <div class="card project-panel mb-4" id="project-schedule">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Project schedule</h2>
                <p class="small text-body-secondary mb-0">See this project’s calendar and upcoming sessions.</p>
            </div>
            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-event-modal">
                <i class="bi bi-calendar-plus me-1"></i>Add event
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
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <form method="POST" action="{{ route('projects.events.visibility', [$project, $event]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="visible_to_customer" value="{{ $event->visible_to_customer ? '0' : '1' }}">
                                        <button class="btn btn-sm {{ $event->visible_to_customer ? 'btn-outline-secondary' : 'btn-outline-primary' }}" type="submit">
                                            <i class="bi {{ $event->visible_to_customer ? 'bi-eye-slash' : 'bi-eye' }} me-1" aria-hidden="true"></i>
                                            {{ $event->visible_to_customer ? 'Hide from customer' : 'Show to customer' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('events.destroy', $event) }}"
                                        onsubmit="return confirm('Remove this event?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger" type="submit" aria-label="Remove {{ $event->title }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="small text-body-secondary project-empty-state p-3">No upcoming events. Select a date in the mini calendar or add an event.</p>
                    @endforelse
                    </div>
                    <a class="btn btn-sm btn-link px-0" href="{{ route('projects.calendar', $project) }}">Open project calendar</a>
                </div>
            </div>
        </div>
    </div>
    @include('projects._event-modal', ['fixedProject' => $project])

    <div class="card project-panel mb-4" aria-labelledby="project-flowcharts-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0" id="project-flowcharts-heading">Flow charts &amp; SOPs</h2>
                <p class="small text-body-secondary mb-0">Link custom charts or duplicate an SOP example for this project.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal"
                    data-bs-target="#project-flowcharts-modal">
                    <i class="bi bi-link-45deg me-1" aria-hidden="true"></i>Manage links
                </button>
                <a class="btn btn-sm btn-primary" href="{{ route('development.flowcharts.create', ['project_id' => $project->id]) }}"
                    data-project-ajax-modal>
                    <i class="bi bi-plus me-1" aria-hidden="true"></i>Create for this project
                </a>
            </div>
        </div>
        <div class="card-body">
            <h3 class="h6 mb-3">Linked flow charts and SOPs</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
                    data-project-flowcharts-table
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
    <div class="modal fade" id="project-flowcharts-modal" tabindex="-1"
        aria-labelledby="project-flowcharts-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="project-flowcharts-modal-title">Manage flow charts &amp; SOPs</h2>
                        <p class="small text-body-secondary mb-0">Link custom charts or make project-specific copies of examples.</p>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('projects.flowcharts.link', $project) }}"
                        data-project-ajax="off" data-project-flowchart-action="link">
                        @csrf
                        <input type="hidden" name="project_flowcharts_action" value="link">
                        <div class="alert alert-danger d-none" role="alert" data-flowcharts-error></div>
                        @if (old('project_flowcharts_action') === 'link')
                            @error('flow_chart_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                            @error('flow_chart_ids.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @endif
                        <label class="form-label" for="project-flow-chart-select">Find custom flow charts to link</label>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <select class="form-select js-project-search" id="project-flow-chart-select"
                                    name="flow_chart_ids[]" multiple required>
                                    @foreach ($availableFlowCharts as $flowChart)
                                        <option value="{{ $flowChart->id }}"
                                            @selected(old('project_flowcharts_action') === 'duplicate'
                                                && in_array((string) $flowChart->id, old('flow_chart_ids', []), true))>
                                            {{ $flowChart->categoryLabel() }}
                                            · {{ $flowChart->title }}{{ $flowChart->subject_name ? ' · '.$flowChart->subject_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text" data-flowcharts-empty @unless ($availableFlowCharts->isEmpty()) hidden @endunless>
                                    No unlinked custom flow charts are available.
                                </div>
                            </div>
                            <div class="col-md-4 d-grid">
                                <button class="btn btn-primary" type="submit" @disabled($availableFlowCharts->isEmpty())>
                                    <i class="bi bi-link me-1" aria-hidden="true"></i>Link selected
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr class="my-4">
                    <form method="POST" action="{{ route('projects.flowcharts.duplicate', $project) }}"
                        data-project-ajax="off" data-project-flowchart-action="duplicate">
                        @csrf
                        <input type="hidden" name="project_flowcharts_action" value="duplicate">
                        <div class="alert alert-danger d-none" role="alert" data-flowcharts-error></div>
                        @if (old('project_flowcharts_action') === 'duplicate')
                            @error('flow_chart_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                            @error('flow_chart_ids.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @endif
                        <label class="form-label" for="project-example-flow-chart-select">Find SOP examples to duplicate</label>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <select class="form-select js-project-search" id="project-example-flow-chart-select"
                                    name="flow_chart_ids[]" multiple required>
                                    @foreach ($availableExamples as $flowChart)
                                        <option value="{{ $flowChart->id }}">
                                            {{ $flowChart->categoryLabel() }}
                                            · {{ $flowChart->title }}{{ $flowChart->subject_name ? ' · '.$flowChart->subject_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($availableExamples->isEmpty())
                                    <div class="form-text">No example charts are available to duplicate.</div>
                                @endif
                            </div>
                            <div class="col-md-4 d-grid">
                                <button class="btn btn-outline-primary" type="submit" @disabled($availableExamples->isEmpty())>
                                    <i class="bi bi-copy me-1" aria-hidden="true"></i>Duplicate for project
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-technology-stack">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Project technology stack</h2>
                <p class="small text-body-secondary mb-0">Record the technologies and versions used by this project, such as PHP, Laravel, and FullCalendar.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">{{ $project->technologyStack->count() }} technologies</span>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#project-technology-create-modal">
                    <i class="bi bi-plus me-1" aria-hidden="true"></i>Add technology
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead><tr><th scope="col">Technology</th><th scope="col">Version</th><th scope="col">Notes</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse ($project->technologyStack as $technology)
                        <tr>
                            <td>{{ $technology->name }}</td>
                            <td>{{ $technology->version ?: '—' }}</td>
                            <td>{{ $technology->notes ?: '—' }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#project-technology-edit-modal-{{ $technology->id }}">
                                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                                </button>
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
        <div class="modal fade" id="project-technology-create-modal" tabindex="-1" aria-labelledby="project-technology-create-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('projects.technology-stack.store', $project) }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="project-technology-create-title">Add technology</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="technology-create-name">Technology</label>
                                <input class="form-control" id="technology-create-name" name="name" value="{{ old('name') }}"
                                    maxlength="120" placeholder="e.g. PHP" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="technology-create-version">Version</label>
                                <input class="form-control" id="technology-create-version" name="version" value="{{ old('version') }}"
                                    maxlength="80" placeholder="e.g. 8.3">
                            </div>
                            <div>
                                <label class="form-label" for="technology-create-notes">Notes <span class="text-body-secondary">(optional)</span></label>
                                <textarea class="form-control" id="technology-create-notes" name="notes" rows="3" maxlength="1000"
                                    placeholder="e.g. Frontend calendar component">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Add technology</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @foreach ($project->technologyStack as $technology)
            <div class="modal fade" id="project-technology-edit-modal-{{ $technology->id }}" tabindex="-1"
                aria-labelledby="project-technology-edit-title-{{ $technology->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.technology-stack.update', [$project, $technology]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="project-technology-edit-title-{{ $technology->id }}">Edit technology</h2>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label" for="technology-{{ $technology->id }}-name">Technology</label>
                                    <input class="form-control" id="technology-{{ $technology->id }}-name" name="name"
                                        value="{{ $technology->name }}" maxlength="120" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="technology-{{ $technology->id }}-version">Version</label>
                                    <input class="form-control" id="technology-{{ $technology->id }}-version" name="version"
                                        value="{{ $technology->version }}" maxlength="80">
                                </div>
                                <div>
                                    <label class="form-label" for="technology-{{ $technology->id }}-notes">Notes <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="technology-{{ $technology->id }}-notes" name="notes"
                                        rows="3" maxlength="1000">{{ $technology->notes }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card project-panel mb-4" id="project-hardware">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Parts & hardware recommendations</h2>
                <p class="small text-body-secondary mb-0">Track suggested parts for this project without changing inventory stock.</p>
            </div>
            <span class="badge text-bg-light border">{{ $project->inventoryParts->count() + $customHardwareParts->count() }} recommended</span>
        </div>
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="h-100 border rounded p-3">
                        <h3 class="h6">Inventory part</h3>
                        @if ($hasInventoryParts)
                            <p class="small text-body-secondary">Search inventory and record its quantity, price, notes, and sources.</p>
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#project-part-recommendation-modal">
                                <i class="bi bi-plus me-1" aria-hidden="true"></i>Add recommendation
                            </button>
                        @else
                            <p class="small text-body-secondary">Add parts to the inventory before recommending them for a project.</p>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.index') }}">Open inventory</a>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="h-100 border rounded p-3">
                        <h3 class="h6">Custom project part</h3>
                        <p class="small text-body-secondary">For project-specific parts that are not in inventory. This does not create an inventory item or change stock.</p>
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#project-custom-part-modal">
                            <i class="bi bi-plus me-1" aria-hidden="true"></i>Add custom part
                        </button>
                    </div>
                </div>
            </div>
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
                                <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-bs-toggle="modal"
                                    data-bs-target="#project-part-price-modal-{{ $part->id }}">
                                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit price
                                </button>
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
                        @if ($customHardwareParts->isEmpty())
                            <tr><td class="text-body-secondary" colspan="5">No parts have been recommended for this project yet.</td></tr>
                        @endif
                    @endforelse
                    @foreach ($customHardwareParts as $item)
                        <tr>
                            <th scope="row">
                                {{ $item->description }}
                                <div class="small text-body-secondary">Custom project part · not in inventory</div>
                            </th>
                            <td>{{ $item->quantity }} needed<br><span class="small text-body-secondary">Stock not tracked</span></td>
                            <td>RM {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td><span class="small text-body-secondary">Added for this project only.</span></td>
                            <td>
                                <div class="small text-body-secondary mb-2">Line total<br><strong class="text-body">RM {{ number_format($item->line_total, 2) }}</strong></div>
                                <form method="POST" action="{{ route('projects.estimate-items.destroy', [$project, $item]) }}"
                                    onsubmit="return confirm('Remove this custom part?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($hasInventoryParts)
            <div class="modal fade" id="project-part-recommendation-modal" tabindex="-1"
                aria-labelledby="project-part-recommendation-title" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.parts.store', $project) }}">
                            @csrf
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="project-part-recommendation-title">Add inventory recommendation</h2>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="project-part-select">Find a part</label>
                                        <select class="form-select @error('inventory_part_id') is-invalid @enderror" id="project-part-select" name="inventory_part_id"
                                            data-search-url="{{ route('projects.parts.search', $project) }}" required>
                                            <option value="">Type at least 2 characters to search inventory</option>
                                            @if ($selectedInventoryPart)
                                                <option value="{{ $selectedInventoryPart->id }}" selected
                                                    data-selling-price="{{ $selectedInventoryPart->selling_price }}"
                                                    data-cost-price="{{ $selectedInventoryPart->cost_price }}">
                                                    {{ $selectedInventoryPart->name }}{{ $selectedInventoryPart->sku ? ' · '.$selectedInventoryPart->sku : '' }} · stock {{ $selectedInventoryPart->stock_quantity }}
                                                </option>
                                            @endif
                                        </select>
                                        @error('inventory_part_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="form-label" for="project-part-quantity">Quantity needed</label>
                                        <input class="form-control @error('quantity') is-invalid @enderror" id="project-part-quantity"
                                            type="number" name="quantity" min="1" max="100000" value="{{ old('quantity', 1) }}" required>
                                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="form-label" for="project-part-price-source">Price</label>
                                        <select class="form-select" id="project-part-price-source" name="price_source" required>
                                            <option value="selling_price" @selected(old('price_source', 'selling_price') === 'selling_price')>Selling</option>
                                            <option value="cost_price" @selected(old('price_source') === 'cost_price')>Cost</option>
                                            <option value="custom" @selected(old('price_source') === 'custom')>Custom</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="project-part-unit-price">Unit price (RM)</label>
                                        <input class="form-control @error('unit_price') is-invalid @enderror" id="project-part-unit-price"
                                            type="number" name="unit_price" min="0" max="99999999" step="0.01"
                                            value="{{ old('unit_price') }}" placeholder="0.00">
                                        @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="project-part-notes">Recommendation notes</label>
                                        <input class="form-control @error('notes') is-invalid @enderror" id="project-part-notes"
                                            type="text" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Compatibility or intended use">
                                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="project-part-url-sources">Source URLs <span class="text-body-secondary">(optional, up to 10)</span></label>
                                        @php
                                            $urlSources = old('url_sources', ['']);
                                        @endphp
                                        <div id="project-part-url-sources" class="d-grid gap-2">
                                            @foreach ($urlSources as $index => $url)
                                                <input class="form-control @error("url_sources.$index") is-invalid @enderror"
                                                    type="url" name="url_sources[]" value="{{ $url }}" maxlength="2048"
                                                    placeholder="https://example.com/part">
                                                @error("url_sources.$index")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            @endforeach
                                        </div>
                                        @error('url_sources')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        <button class="btn btn-sm btn-link px-0 mt-1" id="add-project-part-url-source" type="button">
                                            <i class="bi bi-plus me-1" aria-hidden="true"></i>Add another source
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-plus me-1" aria-hidden="true"></i>Add recommendation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
        <div class="modal fade" id="project-custom-part-modal" tabindex="-1" aria-labelledby="project-custom-part-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('projects.estimate-items.store', $project) }}">
                        @csrf
                        <input type="hidden" name="category" value="hardware">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="project-custom-part-title">Add a custom part</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-body-secondary">This project-specific part is not added to inventory and does not change stock.</p>
                            <div class="mb-3">
                                <label class="form-label" for="custom-part-description">Part name / description</label>
                                <input class="form-control @error('description') is-invalid @enderror" id="custom-part-description"
                                    name="description" value="{{ old('category') === 'hardware' ? old('description') : '' }}" maxlength="255"
                                    placeholder="e.g. Compatible replacement battery" required>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label" for="custom-part-quantity">Quantity</label>
                                    <input class="form-control @error('quantity') is-invalid @enderror" id="custom-part-quantity"
                                        type="number" name="quantity" min="1" max="100000"
                                        value="{{ old('category') === 'hardware' ? old('quantity', 1) : 1 }}" required>
                                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="custom-part-unit-price">Unit price (RM)</label>
                                    <input class="form-control @error('unit_price') is-invalid @enderror" id="custom-part-unit-price"
                                        type="number" name="unit_price" min="0" max="99999999" step="0.01"
                                        value="{{ old('category') === 'hardware' ? old('unit_price') : '' }}" placeholder="0.00" required>
                                    @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Add custom part</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @foreach ($project->inventoryParts as $part)
            @php
                $recommendedUnitPrice = (float) ($part->pivot->unit_price ?? $part->selling_price);
            @endphp
            <div class="modal fade" id="project-part-price-modal-{{ $part->id }}" tabindex="-1"
                aria-labelledby="project-part-price-title-{{ $part->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.parts.update', [$project, $part]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="project-part-price-title-{{ $part->id }}">Edit recommended price</h2>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="fw-semibold">{{ $part->name }}</p>
                                <label class="form-label" for="part-price-source-{{ $part->id }}">Price source</label>
                                <select class="form-select project-part-price-source mb-3"
                                    id="part-price-source-{{ $part->id }}" name="price_source"
                                    data-selling-price="{{ $part->selling_price }}" data-cost-price="{{ $part->cost_price }}">
                                    <option value="selling_price" @selected(($part->pivot->price_source ?? 'selling_price') === 'selling_price')>Selling price</option>
                                    <option value="cost_price" @selected($part->pivot->price_source === 'cost_price')>Cost price</option>
                                    <option value="custom" @selected($part->pivot->price_source === 'custom')>Custom</option>
                                </select>
                                <label class="form-label" for="part-unit-price-{{ $part->id }}">Unit price (RM)</label>
                                <input class="form-control project-part-unit-price" id="part-unit-price-{{ $part->id }}"
                                    type="number" name="unit_price" min="0" max="99999999" step="0.01"
                                    value="{{ number_format($recommendedUnitPrice, 2, '.', '') }}">
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Update price</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Recommended parts subtotal</span>
            <strong>RM {{ number_format($partsEstimateTotal + $customHardwareTotal, 2) }}</strong>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-estimate">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-0">Software &amp; other project costs</h2>
                <p class="small text-body-secondary mb-0">Add software licenses, labor, or other estimated costs to the project total.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">{{ $project->estimateItems->where('category', '!=', 'hardware')->count() }} items</span>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#project-estimate-create-modal">
                    <i class="bi bi-plus me-1" aria-hidden="true"></i>Add cost
                </button>
            </div>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($project->estimateItems->where('category', '!=', 'hardware') as $item)
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
        <div class="modal fade" id="project-estimate-create-modal" tabindex="-1" aria-labelledby="project-estimate-create-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('projects.estimate-items.store', $project) }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="project-estimate-create-title">Add project cost</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label" for="estimate-create-category">Cost type</label>
                                    <select class="form-select" id="estimate-create-category" name="category" required>
                                        <option value="software" @selected(old('category') === 'software')>Software</option>
                                        <option value="labor" @selected(old('category') === 'labor')>Labor</option>
                                        <option value="other" @selected(old('category', 'other') === 'other')>Other</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-9">
                                    <label class="form-label" for="estimate-create-description">Description</label>
                                    <input class="form-control @error('description') is-invalid @enderror" id="estimate-create-description"
                                        name="description" value="{{ old('description') }}" maxlength="255" required
                                        placeholder="e.g. IDE subscription">
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label" for="estimate-create-quantity">Quantity</label>
                                    <input class="form-control @error('quantity') is-invalid @enderror" id="estimate-create-quantity"
                                        type="number" name="quantity" min="1" max="100000" value="{{ old('quantity', 1) }}" required>
                                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6 col-md-8">
                                    <label class="form-label" for="estimate-create-unit-price">Unit price (RM)</label>
                                    <input class="form-control @error('unit_price') is-invalid @enderror" id="estimate-create-unit-price"
                                        type="number" name="unit_price" min="0" max="99999999" step="0.01"
                                        value="{{ old('unit_price') }}" required>
                                    @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Add cost</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Software &amp; other subtotal</span>
            <strong>RM {{ number_format($otherEstimateTotal, 2) }}</strong>
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-customers">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Customer linked to this project</h2>
                <p class="small text-body-secondary mb-0">
                    @if ($canManageProject)
                        Add, change, or unlink the customer from the project.
                    @else
                        Customer details linked to this project.
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">{{ $linkedCustomerCount }} {{ \Illuminate\Support\Str::plural('customer', $linkedCustomerCount) }}</span>
                @if ($canManageProject)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.edit', $project) }}" data-project-ajax-modal>
                        <i class="bi bi-person-gear me-1" aria-hidden="true"></i>Manage customer
                    </a>
                @endif
            </div>
        </div>
        <div class="list-group list-group-flush">
            @if ($project->customerAccount && !$accountHasLinkedRecord)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-3 px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-2">
                            <i class="bi bi-person" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="fw-semibold">{{ $project->customerAccount->name }}</span>
                            <div class="small text-body-secondary">{{ $project->customerAccount->email }}</div>
                            <span class="badge text-bg-light border mt-1">Customer account</span>
                        </div>
                    </div>
                </div>
            @endif
            @forelse ($linkedCustomers as $customer)
                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-2">
                            <i class="bi bi-person-vcard" aria-hidden="true"></i>
                        </span>
                        <div>
                            <a class="fw-semibold" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                            <div class="small text-body-secondary">{{ $customer->email ?: $customer->phone ?: 'No contact details' }}</div>
                            <span class="badge text-bg-light border mt-1">Customer record</span>
                        </div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('customers.show', $customer) }}">
                        <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>View profile
                    </a>
                </div>
            @empty
                @unless ($project->customerAccount)
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex align-items-center gap-3 text-body-secondary">
                            <i class="bi bi-person-plus fs-5" aria-hidden="true"></i>
                            <div>
                                <div class="fw-semibold">No customer linked yet</div>
                                <div class="small">
                                    @if ($canManageProject)
                                        Use Manage customer to select a customer account or record, or choose “No customer linked” to remove one.
                                    @else
                                        A project manager can link a customer account or record here.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endunless
            @endforelse
        </div>
    </div>

    <div class="card project-panel mb-4" id="project-assigned-people">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Assigned people</h2>
                <p class="small text-body-secondary mb-0">Team members responsible for this project.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">{{ $project->assignees->count() }} assigned</span>
                @if (auth()->user()->hasRole('admin'))
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.edit', $project) }}" data-project-ajax-modal>
                        <i class="bi bi-people me-1" aria-hidden="true"></i>Manage assignments
                    </a>
                @else
                    <span class="badge text-bg-light border">Admin-managed</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if ($project->assignees->isNotEmpty())
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($project->assignees as $assignee)
                        <span class="badge text-bg-light border d-inline-flex align-items-center gap-2 px-3 py-2">
                            <i class="bi bi-person-circle text-primary" aria-hidden="true"></i>
                            <span>{{ $assignee->name }}</span>
                        </span>
                    @endforeach
                </div>
                @if (auth()->user()->hasRole('admin'))
                    <p class="small text-body-secondary mt-3 mb-0">Use Manage assignments to add or remove team members.</p>
                @endif
            @else
                <div class="d-flex align-items-center gap-3 text-body-secondary">
                    <i class="bi bi-people fs-5" aria-hidden="true"></i>
                    <div>
                        <div class="fw-semibold">No people assigned yet</div>
                        @if (auth()->user()->hasRole('admin'))
                            <div class="small">Use Manage assignments to add or remove team members.</div>
                        @else
                            <div class="small">Ask a project admin to update the team assignments.</div>
                        @endif
                    </div>
                </div>
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
                <h2 class="h5 mb-0">Project roadmap</h2>
                <p class="small text-body-secondary mb-0">Planned task start and due dates; tasks without dates are listed as unscheduled.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.tasks.create', $project) }}">Add scheduled task</a>
        </div>
        <div class="card-body">
            @if ($ganttTasks->isEmpty())
                <p class="text-body-secondary mb-0">Add tasks to display the project roadmap.</p>
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
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">{{ $project->tasks->count() }}</span>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.tasks.index', $project) }}">
                    All project tasks
                </a>
            </div>
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
                                            <i class="bi bi-arrow-down text-body-tertiary" aria-hidden="true"></i>
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
                            <i class="bi bi-plus me-1"></i>Add subtask
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <div class="card-body text-center py-5">
                <p class="text-body-secondary">No tasks in this project yet.</p>
                <a class="btn btn-primary" href="{{ route('projects.tasks.create', $project) }}" data-project-ajax-modal>Add the first task</a>
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
        const initializeProjectLightbox = () => {
            if (window.GLightbox) {
                window.GLightbox({ selector: '.attachment-lightbox' });
            }
        };
        document.addEventListener('DOMContentLoaded', initializeProjectLightbox);
        window.addEventListener('project:content-updated', initializeProjectLightbox);
    </script>
@endpush

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-project-photo-upload-form]');
            if (!(form instanceof HTMLFormElement)) return;

            const fileInput = form.querySelector('input[type="file"][name="photos[]"]');
            const preview = form.querySelector('[data-project-photo-preview]');
            const errorBox = form.querySelector('[data-project-photo-error]');
            const uploadModal = form.closest('.modal');
            const captionModal = document.getElementById('project-photo-caption-modal');
            const captionInput = captionModal?.querySelector('[data-project-photo-caption-input]');
            const captionImage = captionModal?.querySelector('[data-project-photo-caption-image]');
            const captionFilename = captionModal?.querySelector('[data-project-photo-caption-filename]');
            const imageExtensions = {
                'image/jpeg': 'jpg',
                'image/png': 'png',
                'image/webp': 'webp',
            };
            const maxPhotos = 8;
            const maxPhotoBytes = 10 * 1024 * 1024;
            let selectedFiles = [];
            let captions = [];
            let programmaticChange = false;
            let captionEditorState = null;

            if (!(fileInput instanceof HTMLInputElement) || !preview || !uploadModal || !captionModal || !captionInput) return;

            const showError = (message) => {
                errorBox.textContent = message;
                errorBox.classList.remove('d-none');
            };
            const clearError = () => {
                errorBox.textContent = '';
                errorBox.classList.add('d-none');
            };
            const getFileKey = (file) => `${file.name}\0${file.size}\0${file.lastModified}`;
            const releasePreviewUrls = () => {
                preview.querySelectorAll('img[data-object-url]').forEach((image) => {
                    const objectUrl = image.dataset.objectUrl;
                    delete image.dataset.objectUrl;
                    const revoke = () => URL.revokeObjectURL(objectUrl);
                    if (image.complete) {
                        revoke();
                    } else {
                        image.addEventListener('load', revoke, { once: true });
                        image.addEventListener('error', revoke, { once: true });
                    }
                });
            };
            const renderPreview = () => {
                releasePreviewUrls();
                preview.replaceChildren();
                if (!selectedFiles.length) {
                    preview.classList.add('d-none');
                    return;
                }

                selectedFiles.forEach((file, index) => {
                    const card = document.createElement('div');
                    card.className = 'project-photo-preview-card';

                    const imageButton = document.createElement('button');
                    imageButton.className = 'project-photo-image-button';
                    imageButton.type = 'button';
                    imageButton.dataset.projectPhotoCaptionEdit = '';
                    imageButton.dataset.index = String(index);
                    imageButton.setAttribute('aria-label', `Add or edit caption for ${file.name}`);
                    const image = document.createElement('img');
                    image.className = 'project-photo-upload-preview';
                    image.alt = `Preview of ${file.name}`;
                    image.dataset.objectUrl = URL.createObjectURL(file);
                    image.src = image.dataset.objectUrl;
                    imageButton.append(image);

                    const details = document.createElement('div');
                    details.className = 'project-photo-preview-details';
                    const filename = document.createElement('span');
                    filename.className = 'small text-truncate';
                    filename.textContent = file.name;
                    const removeButton = document.createElement('button');
                    removeButton.className = 'btn btn-sm btn-outline-secondary';
                    removeButton.type = 'button';
                    removeButton.dataset.projectPhotoRemove = '';
                    removeButton.dataset.index = String(index);
                    removeButton.setAttribute('aria-label', `Remove ${file.name}`);
                    const removeIcon = document.createElement('span');
                    removeIcon.className = 'bi bi-x-lg';
                    removeIcon.setAttribute('aria-hidden', 'true');
                    removeButton.append(removeIcon);

                    const captionButton = document.createElement('button');
                    captionButton.className = 'btn btn-sm btn-outline-secondary project-photo-caption-button';
                    captionButton.type = 'button';
                    captionButton.dataset.projectPhotoCaptionEdit = '';
                    captionButton.dataset.index = String(index);
                    captionButton.textContent = captions[index] || 'Add caption';
                    captionButton.setAttribute('aria-label', `Add or edit caption for ${file.name}`);

                    const captionField = document.createElement('input');
                    captionField.type = 'hidden';
                    captionField.name = 'captions[]';
                    captionField.value = captions[index] || '';

                    details.append(filename, removeButton);
                    card.append(imageButton, details, captionButton, captionField);
                    preview.append(card);
                });
                preview.classList.remove('d-none');
            };
            const setPhotoFiles = (files, nextCaptions = captions) => {
                if (files.length > maxPhotos) {
                    showError(`Choose up to ${maxPhotos} photos per upload.`);
                    return false;
                }
                if (files.some((file) => !imageExtensions[file.type])) {
                    showError('Only JPEG, PNG, and WebP images can be added.');
                    return false;
                }
                if (files.some((file) => file.size > maxPhotoBytes)) {
                    showError('Each photo must be 10 MB or smaller.');
                    return false;
                }

                try {
                    const transfer = new DataTransfer();
                    files.forEach((file) => transfer.items.add(file));
                    fileInput.files = transfer.files;
                } catch (error) {
                    console.error('Could not attach the project photos.', error);
                    showError('Could not attach the photos. Choose files from your device instead.');
                    return false;
                }

                selectedFiles = files;
                captions = files.map((_, index) => nextCaptions[index] || '');
                programmaticChange = true;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            };
            const addPhotos = (files) => {
                const nextFiles = [...selectedFiles];
                const nextCaptions = [...captions];
                files.forEach((file) => {
                    if (!nextFiles.some((existing) => getFileKey(existing) === getFileKey(file))) {
                        nextFiles.push(file);
                        nextCaptions.push('');
                    }
                });
                return setPhotoFiles(nextFiles, nextCaptions);
            };
            const hideModal = (modal) => new Promise((resolve) => {
                if (!modal.classList.contains('show')) {
                    resolve();
                    return;
                }
                modal.addEventListener('hidden.bs.modal', resolve, { once: true });
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            });
            const openCaptionEditor = async (index) => {
                const file = selectedFiles[index];
                const card = preview.querySelectorAll('.project-photo-preview-card')[index];
                if (!file || !card || captionEditorState) return;

                captionEditorState = { index };
                const image = card.querySelector('img');
                captionImage.src = image?.src || '';
                captionImage.alt = `Preview of ${file.name}`;
                captionFilename.textContent = file.name;
                captionInput.value = captions[index] || '';
                captionInput.classList.remove('is-invalid');

                await hideModal(uploadModal);
                bootstrap.Modal.getOrCreateInstance(captionModal).show();
            };
            const requestCaptionEditor = (index) => {
                openCaptionEditor(index).catch((error) => {
                    captionEditorState = null;
                    console.error('Could not open the project photo caption editor.', error);
                    showError('Could not open the caption editor. Please try again.');
                });
            };

            fileInput.addEventListener('change', () => {
                const incomingFiles = Array.from(fileInput.files || []);
                if (programmaticChange) {
                    programmaticChange = false;
                    clearError();
                    renderPreview();
                    return;
                }

                const nextFiles = [...selectedFiles];
                const nextCaptions = [...captions];
                incomingFiles.forEach((file) => {
                    if (!nextFiles.some((existing) => getFileKey(existing) === getFileKey(file))) {
                        nextFiles.push(file);
                        nextCaptions.push('');
                    }
                });
                if (!setPhotoFiles(nextFiles, nextCaptions)) {
                    const error = errorBox.textContent;
                    setPhotoFiles(selectedFiles, captions);
                    showError(error);
                }
            });

            form.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const captionButton = target?.closest('[data-project-photo-caption-edit]');
                if (captionButton) {
                    requestCaptionEditor(Number(captionButton.dataset.index));
                    return;
                }

                const removeButton = target?.closest('[data-project-photo-remove]');
                if (removeButton) {
                    const index = Number(removeButton.dataset.index);
                    const nextFiles = [...selectedFiles];
                    const nextCaptions = [...captions];
                    nextFiles.splice(index, 1);
                    nextCaptions.splice(index, 1);
                    setPhotoFiles(nextFiles, nextCaptions);
                    return;
                }

                const dropzone = target?.closest('[data-project-photo-dropzone]');
                if (dropzone && !target?.closest('button, label, textarea, input')) {
                    dropzone.querySelector('[data-project-photo-paste]')?.focus();
                }
            });

            const dropzone = form.querySelector('[data-project-photo-dropzone]');
            dropzone?.addEventListener('dragover', (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragging');
            });
            dropzone?.addEventListener('dragleave', (event) => {
                if (event.relatedTarget instanceof Node && dropzone.contains(event.relatedTarget)) return;
                dropzone.classList.remove('is-dragging');
            });
            dropzone?.addEventListener('drop', (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragging');
                const files = Array.from(event.dataTransfer?.files || []);
                if (files.length) addPhotos(files);
            });

            form.addEventListener('paste', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                if (!target?.closest('[data-project-photo-paste]')) return;

                const clipboardFiles = [
                    ...Array.from(event.clipboardData?.items || [])
                        .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
                        .map((item) => item.getAsFile())
                        .filter((file) => file instanceof File),
                    ...Array.from(event.clipboardData?.files || []).filter((file) => file.type.startsWith('image/')),
                ];
                const sourceFile = clipboardFiles[0];
                if (sourceFile) {
                    event.preventDefault();
                    const extension = imageExtensions[sourceFile.type];
                    if (!extension) {
                        showError('Only JPEG, PNG, and WebP images can be pasted.');
                        return;
                    }
                    addPhotos([new File([sourceFile], `pasted-project-photo.${extension}`, {
                        type: sourceFile.type,
                        lastModified: Date.now(),
                    })]);
                    return;
                }

                if (typeof navigator.clipboard?.read !== 'function') {
                    event.preventDefault();
                    showError('The clipboard does not contain an image. Copy a photo or choose a file.');
                    return;
                }

                event.preventDefault();
                navigator.clipboard.read().then(async (clipboardItems) => {
                    for (const clipboardItem of clipboardItems) {
                        const imageType = Object.keys(imageExtensions)
                            .find((type) => clipboardItem.types.includes(type));
                        if (!imageType) continue;

                        const file = new File(
                            [await clipboardItem.getType(imageType)],
                            `pasted-project-photo.${imageExtensions[imageType]}`,
                            { type: imageType, lastModified: Date.now() },
                        );
                        addPhotos([file]);
                        return;
                    }
                    showError('The clipboard does not contain a JPEG, PNG, or WebP image. Copy a photo or choose a file.');
                }).catch((error) => {
                    console.error('Could not read an image from the clipboard.', error);
                    showError('Could not read an image from the clipboard. Choose a file from your device instead.');
                });
            }, true);

            captionModal.addEventListener('shown.bs.modal', () => captionInput.focus());
            captionModal.addEventListener('hidden.bs.modal', () => {
                captionEditorState = null;
                if (form.isConnected && uploadModal.isConnected) {
                    bootstrap.Modal.getOrCreateInstance(uploadModal).show();
                }
            });
            captionInput.addEventListener('input', () => captionInput.classList.remove('is-invalid'));
            captionModal.querySelector('[data-project-photo-caption-save]')?.addEventListener('click', () => {
                const caption = captionInput.value.trim();
                const state = captionEditorState;
                if (!state || !caption) {
                    captionInput.classList.add('is-invalid');
                    captionInput.focus();
                    return;
                }

                captions[state.index] = caption;
                setPhotoFiles(selectedFiles, captions);
                bootstrap.Modal.getInstance(captionModal)?.hide();
            });

            form.addEventListener('submit', (event) => {
                if (!selectedFiles.length) {
                    event.preventDefault();
                    showError('Choose at least one photo to upload.');
                    return;
                }

                const missingCaptionIndex = captions.findIndex((caption) => !caption.trim());
                if (missingCaptionIndex !== -1) {
                    event.preventDefault();
                    showError('Add a caption to each photo before uploading.');
                    requestCaptionEditor(missingCaptionIndex);
                }
            });
        })();
    </script>
@endpush

@push('scripts')
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    const initializeProjectDetails = () => {
        const miniCalendarElement = document.getElementById('project-mini-calendar');
        if (miniCalendarElement && window.FullCalendar) {
            const projectCalendar = new FullCalendar.Calendar(miniCalendarElement, {
                initialView: 'dayGridMonth',
                height: 'auto',
                fixedWeekCount: false,
                headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
                dayMaxEvents: true,
                events: {
                    url: @json(route('projects.calendar.events', $project)),
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
                    const startInput = document.getElementById('calendar-event-start');
                    startInput.value = `${info.dateStr.slice(0, 10)}T09:00`;
                    startInput.dispatchEvent(new Event('input', { bubbles: true }));
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

        if (partSelect && !partSelect.dataset.priceSyncInitialized) {
            partSelect.dataset.priceSyncInitialized = 'true';
            partSelect.addEventListener('change', updateNewPartPrice);
        }
        if (partSelect && window.jQuery && !window.jQuery(partSelect).hasClass('select2-hidden-accessible')) {
            const $partSelect = window.jQuery(partSelect);
            const $partModal = $partSelect.closest('.modal');
            $partSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search inventory by name or SKU',
                minimumInputLength: 2,
                dropdownParent: $partModal.length ? $partModal : window.jQuery(document.body),
                ajax: {
                    url: partSelect.dataset.searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term }),
                    processResults: (payload) => payload,
                },
            });
            $partSelect.on('select2:select', (event) => {
                const selectedOption = partSelect.selectedOptions[0];
                selectedOption.dataset.sellingPrice = event.params.data.selling_price;
                selectedOption.dataset.costPrice = event.params.data.cost_price;
                updateNewPartPrice();
            });
        }
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
    };
    document.addEventListener('DOMContentLoaded', initializeProjectDetails);
    window.addEventListener('project:content-updated', initializeProjectDetails);
</script>
@endpush

    @push('scripts')
        <script>
            (() => {
                const projectRoot = document.querySelector('.project-show[data-project-id]');
                if (!projectRoot) return;

                @if (old('project_flowcharts_action') && $errors->any())
                    document.addEventListener('DOMContentLoaded', () => {
                        const modal = document.getElementById('project-flowcharts-modal');
                        if (modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
                    });
                @endif

                const projectId = Number(projectRoot.dataset.projectId);
                const table = document.querySelector('[data-project-flowcharts-table]');
                const reloadFlowCharts = () => {
                    if (table && window.jQuery?.fn?.DataTable?.isDataTable(table)) {
                        window.jQuery(table).DataTable().ajax.reload(null, false);
                    }
                };

                window.addEventListener('project:flow-chart-saved', (event) => {
                    if (Number(event.detail?.projectId) === projectId) reloadFlowCharts();
                });

                document.addEventListener('submit', async (event) => {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

                    const actionType = form.dataset.projectFlowchartAction;
                    const isUnlink = form.hasAttribute('data-project-flowchart-unlink');
                    if (!actionType && !isUnlink) return;

                    event.preventDefault();
                    if (form.dataset.projectFlowchartSubmitting === 'true') return;
                    form.dataset.projectFlowchartSubmitting = 'true';
                    form.setAttribute('aria-busy', 'true');

                    const button = form.querySelector('button[type="submit"]');
                    const originalButton = button?.innerHTML;
                    if (button) {
                        button.disabled = true;
                        button.textContent = 'Saving…';
                    }
                    const error = form.querySelector('[data-flowcharts-error]');
                    error?.classList.add('d-none');

                    try {
                        const response = await fetch(form.action, {
                            method: form.method.toUpperCase(),
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        let result;
                        try {
                            result = await response.json();
                        } catch {
                            throw new Error(`Could not save this change (HTTP ${response.status}).`);
                        }

                        if (!response.ok) {
                            const errors = Object.values(result.errors || {}).flat();
                            const message = errors.join(' ') || result.message || 'Could not save this change.';
                            if (error) {
                                error.textContent = message;
                                error.classList.remove('d-none');
                            }
                            window.showAppToast?.(message, 'danger', 'Flow charts & SOPs');
                            return;
                        }

                        if (actionType) {
                            const select = form.querySelector('select[name="flow_chart_ids[]"]');
                            if (select) {
                                if (actionType === 'link') {
                                    [...select.selectedOptions].forEach((option) => option.remove());
                                    if (window.jQuery) {
                                        window.jQuery(select).val(null).trigger('change');
                                    }
                                    if (select.options.length === 0) {
                                        form.querySelector('[data-flowcharts-empty]').hidden = false;
                                    }
                                } else if (window.jQuery) {
                                    window.jQuery(select).val(null).trigger('change');
                                } else {
                                    [...select.options].forEach((option) => { option.selected = false; });
                                }
                            }
                        }

                        reloadFlowCharts();
                        window.showAppToast?.(result.message || 'Flow chart changes saved.', 'success', 'Flow charts & SOPs');
                    } catch (requestError) {
                        const message = requestError.message || 'Could not save this change.';
                        if (error) {
                            error.textContent = message;
                            error.classList.remove('d-none');
                        }
                        window.showAppToast?.(message, 'danger', 'Flow charts & SOPs');
                    } finally {
                        form.removeAttribute('aria-busy');
                        delete form.dataset.projectFlowchartSubmitting;
                        if (button) {
                            const linkSelect = form.dataset.projectFlowchartAction === 'link'
                                ? form.querySelector('select[name="flow_chart_ids[]"]')
                                : null;
                            button.disabled = Boolean(linkSelect && linkSelect.options.length === 0);
                            button.innerHTML = originalButton;
                        }
                    }
                });
            })();
        </script>
    @endpush
