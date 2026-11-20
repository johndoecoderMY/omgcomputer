@extends('layouts.app')

@section('title', 'Planning · '.$project->name)

@section('content')
<div class="container-fluid py-3 project-planning" data-project-ajax-root>
    <x-page-header eyebrow="Project planning" :title="$project->name"
        description="Define the project brief, requirements, modules, delivery milestones, and validation plan."
        :breadcrumbs="[['label' => $project->name, 'url' => route('projects.show', $project)], ['label' => 'Planning']]"
        :back-url="route('projects.show', $project)" back-label="Back to project">
        <x-slot:actions>
            <form method="POST" action="{{ route('projects.export', $project) }}" target="_blank">
                @csrf
                <input type="hidden" name="sections[]" value="planning">
                <input type="hidden" name="flowchart_mode" value="all">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>Export planning PDF
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['Status', ucfirst(str_replace('_', ' ', $project->planning_status)), 'bi-flag', '#planning-brief-heading', 'text-primary'],
            ['Requirements', $requirements->count(), 'bi-list-check', '#planning-requirements-heading', 'text-body'],
            ['Modules & features', $modules->count().' modules · '.$modules->sum(fn ($module) => $module->features->count()).' features', 'bi-boxes', '#planning-modules-heading', 'text-body'],
            ['Milestones', $milestones->count(), 'bi-signpost-split', '#planning-roadmap-heading', 'text-body'],
            ['Health', $projectHealth, 'bi-heart-pulse', '#planning-quality-heading', $projectHealth === 'At risk' ? 'text-danger' : 'text-success'],
        ] as [$label, $value, $icon, $target, $color])
            <div class="{{ $loop->last ? 'col-12 col-lg' : 'col-6 col-lg' }}">
                <a class="card h-100 text-decoration-none text-reset" href="{{ $target }}">
                    <div class="card-body d-flex align-items-start gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-2" aria-hidden="true">
                            <i class="bi {{ $icon }}"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary text-uppercase fw-semibold">{{ $label }}</div>
                            <div class="fw-semibold {{ $color }}">{{ $value }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <p class="small text-body-secondary mb-4"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Health is marked at risk when a milestone is overdue or a high/critical issue remains unresolved.</p>

    <div class="card mb-4" role="region" aria-labelledby="planning-brief-heading" id="planning-brief">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h2 class="h5 mb-1" id="planning-brief-heading">Project brief</h2>
                <p class="small text-body-secondary mb-0">A quick overview of this project's goals, scope, and delivery targets.</p>
            </div>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#edit-project-brief-modal">
                <i class="bi bi-pencil me-2" aria-hidden="true"></i>Edit brief
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ([
                    ['Status', ucfirst(str_replace('_', ' ', $project->planning_status)), 'bi-flag'],
                    ['Start date', $project->start_date?->format('M j, Y') ?? 'Not set', 'bi-calendar-event'],
                    ['Target end', $project->target_end_date?->format('M j, Y') ?? 'Not set', 'bi-calendar-check'],
                    ['Budget', $project->budget !== null ? 'RM '.number_format((float) $project->budget, 2) : 'Not set', 'bi-cash'],
                ] as [$label, $value, $icon])
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-body-secondary mb-1"><i class="bi {{ $icon }} me-1" aria-hidden="true"></i>{{ $label }}</div>
                            <div class="fw-semibold">{{ $value }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <details class="mt-3">
                <summary class="fw-semibold">Read the full project brief</summary>
                <div class="row g-3 mt-1">
                    @foreach ([
                        'Business background' => $project->business_background,
                        'User roles and responsibilities' => $project->user_roles,
                        'Objectives' => $project->objectives,
                        'Problem statement' => $project->problem_statement,
                        'Proposed solution' => $project->proposed_solution,
                        'In scope' => $project->scope_in,
                        'Out of scope' => $project->scope_out,
                    ] as $label => $value)
                        <div class="col-md-6">
                            <h3 class="h6 mb-1">{{ $label }}</h3>
                            <p class="small text-body-secondary mb-0" style="white-space: pre-line">{{ filled($value) ? $value : 'Not provided.' }}</p>
                        </div>
                    @endforeach
                </div>
            </details>
        </div>
    </div>
    @php
        $restoreBrief = old('_planning_modal') === 'edit-project-brief-modal';
    @endphp
    <div class="modal fade" id="edit-project-brief-modal" tabindex="-1" aria-labelledby="edit-project-brief-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('projects.planning.overview.update', $project) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="_planning_modal" value="edit-project-brief-modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5" id="edit-project-brief-title">Edit project brief</h2>
                            <p class="small text-body-secondary mb-0">Keep the project's purpose, boundaries, and delivery targets in one place.</p>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            @foreach ([
                                'business_background' => ['Business background', $project->business_background],
                                'user_roles' => ['User roles and responsibilities', $project->user_roles],
                                'objectives' => ['Objectives', $project->objectives],
                                'problem_statement' => ['Problem statement', $project->problem_statement],
                                'proposed_solution' => ['Proposed solution', $project->proposed_solution],
                                'scope_in' => ['In scope', $project->scope_in],
                                'scope_out' => ['Out of scope', $project->scope_out],
                            ] as $field => [$label, $value])
                                <div class="col-md-6">
                                    <label class="form-label" for="brief-{{ $field }}">{{ $label }}</label>
                                    <textarea class="form-control" id="brief-{{ $field }}" name="{{ $field }}" rows="3" maxlength="10000">{{ $restoreBrief ? old($field, $value) : $value }}</textarea>
                                </div>
                            @endforeach
                            <div class="col-md-6">
                                <label class="form-label" for="planning_status">Project status</label>
                                <select class="form-select" id="planning_status" name="planning_status" required>
                                    @foreach (['draft' => 'Draft', 'planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($restoreBrief ? old('planning_status', $project->planning_status) : $project->planning_status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="start_date">Start date</label>
                                <input class="form-control" type="date" id="start_date" name="start_date" value="{{ $restoreBrief ? old('start_date', $project->start_date?->format('Y-m-d')) : $project->start_date?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="target_end_date">Target end date</label>
                                <input class="form-control" type="date" id="target_end_date" name="target_end_date" value="{{ $restoreBrief ? old('target_end_date', $project->target_end_date?->format('Y-m-d')) : $project->target_end_date?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="budget">Budget (RM)</label>
                                <input class="form-control" type="number" step="0.01" min="0" id="budget" name="budget" value="{{ $restoreBrief ? old('budget', $project->budget) : $project->budget }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Save brief</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card mb-4" role="region" aria-labelledby="planning-requirements-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1" id="planning-requirements-heading">Requirements</h2>
                <p class="small text-body-secondary mb-0">Define what the project must deliver. Link tasks on the development board to track the work.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-requirement-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add requirement
            </button>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($requirements as $requirement)
                <article class="list-group-item p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="fw-semibold">{{ $requirement->code ?: 'Requirement' }}</span>
                                <span class="badge text-bg-light border">{{ $requirementTypes[$requirement->requirement_type] ?? $requirement->requirement_type }}</span>
                                <span class="badge text-bg-light border">{{ $requirementPriorities[$requirement->priority] ?? $requirement->priority }}</span>
                                <span class="badge text-bg-light border">{{ $requirementStatuses[$requirement->status] ?? $requirement->status }}</span>
                            </div>
                            <p class="mb-2">{{ $requirement->requirement }}</p>
                            @if ($requirement->user_story)
                                <p class="small text-body-secondary mb-2">{{ $requirement->user_story }}</p>
                            @endif
                            @if ($requirement->acceptance_criteria)
                                <p class="small mb-2" style="white-space: pre-line">{{ $requirement->acceptance_criteria }}</p>
                            @endif
                            <div class="small text-body-secondary">
                                {{ $requirement->tasks_count }} linked tasks
                                @if ($requirement->module)
                                    <span class="mx-1">·</span>{{ $requirement->module->name }}
                                @endif
                                @if ($requirement->parent)
                                    <span class="mx-1">·</span>Parent: {{ $requirement->parent->code }}
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal"
                                data-bs-target="#edit-requirement-modal-{{ $requirement->id }}">Edit</button>
                            <form method="POST" action="{{ route('projects.planning.requirements.destroy', [$project, $requirement]) }}"
                                onsubmit="return confirm('Remove this requirement? Linked task references will be cleared.');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                        </div>
                    </div>
                </article>
                <div class="modal fade" id="edit-requirement-modal-{{ $requirement->id }}" tabindex="-1"
                    aria-labelledby="edit-requirement-title-{{ $requirement->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('projects.planning.requirements.update', [$project, $requirement]) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="_requirement_form" value="edit-requirement-{{ $requirement->id }}">
                                <div class="modal-header">
                                    <h2 class="modal-title fs-5" id="edit-requirement-title-{{ $requirement->id }}">Edit {{ $requirement->code }}</h2>
                                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @include('projects.partials.requirement-fields', [
                                        'requirement' => $requirement,
                                        'formId' => 'edit-requirement-'.$requirement->id,
                                    ])
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary" type="submit">Save requirement</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center p-4">
                    <i class="bi bi-list-check fs-2 text-body-secondary" aria-hidden="true"></i>
                    <p class="fw-semibold mt-2 mb-1">No requirements yet</p>
                    <p class="small text-body-secondary mb-3">Write down what this project needs to deliver, then link tasks to track the work.</p>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-requirement-modal">Add the first requirement</button>
                </div>
            @endforelse
        </div>
    </div>
    <div class="modal fade" id="create-requirement-modal" tabindex="-1"
        aria-labelledby="create-requirement-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('projects.planning.requirements.store', $project) }}">
                    @csrf
                    <input type="hidden" name="_requirement_form" value="create-requirement">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="create-requirement-title">Add requirement</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('projects.partials.requirement-fields', [
                            'requirement' => null,
                            'formId' => 'create-requirement',
                        ])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Add requirement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card mb-4" role="region" aria-labelledby="planning-modules-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1" id="planning-modules-heading">Modules and features</h2>
                <p class="small text-body-secondary mb-0">Group the work into system areas, then add the capabilities each area needs.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-module-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add module
            </button>
        </div>
        @if ($modules->isEmpty())
            <div class="card-body">
                <div class="text-center rounded-3 bg-body-tertiary p-4">
                    <i class="bi bi-boxes fs-2 text-body-secondary" aria-hidden="true"></i>
                    <p class="fw-semibold mt-2 mb-1">No modules yet</p>
                    <p class="small text-body-secondary mb-3">Start by adding a system area, such as Accounts, Payments, or Reporting.</p>
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-module-modal">Add the first module</button>
                </div>
            </div>
        @else
            <div class="accordion accordion-flush" id="planning-modules-accordion">
                @foreach ($modules as $module)
                    <article class="accordion-item">
                        <h3 class="accordion-header" id="module-heading-{{ $module->id }}">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse"
                                data-bs-target="#module-collapse-{{ $module->id }}" aria-expanded="false"
                                aria-controls="module-collapse-{{ $module->id }}">
                                <span class="d-flex flex-wrap align-items-center gap-2 pe-2">
                                    <span class="fw-semibold">{{ $module->name }}</span>
                                    <span class="badge text-bg-light border">{{ $module->features->count() }} {{ \Illuminate\Support\Str::plural('feature', $module->features->count()) }}</span>
                                </span>
                            </button>
                        </h3>
                        <div id="module-collapse-{{ $module->id }}" class="accordion-collapse collapse"
                            aria-labelledby="module-heading-{{ $module->id }}" data-bs-parent="#planning-modules-accordion">
                            <div class="accordion-body">
                                @if ($module->description)
                                    <p class="text-body-secondary">{{ $module->description }}</p>
                                @endif
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#edit-module-modal-{{ $module->id }}">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit module
                                    </button>
                                    <form method="POST" action="{{ route('projects.planning.modules.destroy', [$project, $module]) }}"
                                        onsubmit="return confirm('Remove this module and its features?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash me-1" aria-hidden="true"></i>Remove module</button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#create-feature-modal-{{ $module->id }}">
                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add feature
                                    </button>
                                </div>
                                @if ($module->features->isNotEmpty())
                                    <div class="list-group">
                                        @foreach ($module->features as $feature)
                                            <div class="list-group-item">
                                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                                    <div class="min-w-0">
                                                        <h4 class="h6 mb-2">{{ $feature->name }}</h4>
                                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                                            <span class="badge text-bg-light border">{{ $requirementPriorities[$feature->priority] ?? ucfirst($feature->priority) }}</span>
                                                            <span class="badge text-bg-light border">{{ $requirementStatuses[$feature->status] ?? ucfirst($feature->status) }}</span>
                                                        </div>
                                                        @if ($feature->description)
                                                            <p class="small text-body-secondary mb-0">{{ $feature->description }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#edit-feature-modal-{{ $feature->id }}">Edit</button>
                                                        <form method="POST" action="{{ route('projects.planning.features.destroy', [$project, $module, $feature]) }}"
                                                            onsubmit="return confirm('Remove this feature?');">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove {{ $feature->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="small text-body-secondary mb-0">No features in this module yet. Add one to describe a capability to deliver.</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @php
            $restoreCreateModule = old('_planning_modal') === 'create-module-modal';
        @endphp
        <div class="modal fade" id="create-module-modal" tabindex="-1" aria-labelledby="create-module-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('projects.planning.modules.store', $project) }}">
                        @csrf
                        <input type="hidden" name="_planning_modal" value="create-module-modal">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="create-module-title">Add module</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="create-module-name">Module name</label>
                                <input class="form-control" id="create-module-name" name="name" value="{{ $restoreCreateModule ? old('name') : '' }}" maxlength="180" required>
                            </div>
                            <div>
                                <label class="form-label" for="create-module-description">Description <span class="text-body-secondary">(optional)</span></label>
                                <textarea class="form-control" id="create-module-description" name="description" rows="3" maxlength="5000">{{ $restoreCreateModule ? old('description') : '' }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Add module</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @foreach ($modules as $module)
            @php
                $restoreEditModule = old('_planning_modal') === 'edit-module-modal-'.$module->id;
            @endphp
            <div class="modal fade" id="edit-module-modal-{{ $module->id }}" tabindex="-1" aria-labelledby="edit-module-title-{{ $module->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.planning.modules.update', [$project, $module]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="_planning_modal" value="edit-module-modal-{{ $module->id }}">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="edit-module-title-{{ $module->id }}">Edit module</h2>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label" for="edit-module-name-{{ $module->id }}">Module name</label>
                                    <input class="form-control" id="edit-module-name-{{ $module->id }}" name="name" value="{{ $restoreEditModule ? old('name', $module->name) : $module->name }}" maxlength="180" required>
                                </div>
                                <div>
                                    <label class="form-label" for="edit-module-description-{{ $module->id }}">Description <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="edit-module-description-{{ $module->id }}" name="description" rows="3" maxlength="5000">{{ $restoreEditModule ? old('description', $module->description) : $module->description }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Save module</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @php
                $restoreCreateFeature = old('_planning_modal') === 'create-feature-modal-'.$module->id;
            @endphp
            <div class="modal fade" id="create-feature-modal-{{ $module->id }}" tabindex="-1" aria-labelledby="create-feature-title-{{ $module->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.planning.features.store', [$project, $module]) }}">
                            @csrf
                            <input type="hidden" name="_planning_modal" value="create-feature-modal-{{ $module->id }}">
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title fs-5" id="create-feature-title-{{ $module->id }}">Add feature</h2>
                                    <p class="small text-body-secondary mb-0">For {{ $module->name }}</p>
                                </div>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label" for="create-feature-name-{{ $module->id }}">Feature name</label>
                                    <input class="form-control" id="create-feature-name-{{ $module->id }}" name="name" value="{{ $restoreCreateFeature ? old('name') : '' }}" maxlength="180" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="create-feature-description-{{ $module->id }}">Description <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="create-feature-description-{{ $module->id }}" name="description" rows="3" maxlength="5000">{{ $restoreCreateFeature ? old('description') : '' }}</textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="create-feature-priority-{{ $module->id }}">Priority</label>
                                        <select class="form-select" id="create-feature-priority-{{ $module->id }}" name="priority">
                                            @foreach ($requirementPriorities as $key => $label)<option value="{{ $key }}" @selected(($restoreCreateFeature ? old('priority', 'must') : 'must') === $key)>{{ $label }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="create-feature-status-{{ $module->id }}">Status</label>
                                        <select class="form-select" id="create-feature-status-{{ $module->id }}" name="status">
                                            @foreach ($requirementStatuses as $key => $label)<option value="{{ $key }}" @selected(($restoreCreateFeature ? old('status', 'planned') : 'planned') === $key)>{{ $label }}</option>@endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Add feature</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @foreach ($module->features as $feature)
                @php
                    $restoreEditFeature = old('_planning_modal') === 'edit-feature-modal-'.$feature->id;
                @endphp
                <div class="modal fade" id="edit-feature-modal-{{ $feature->id }}" tabindex="-1" aria-labelledby="edit-feature-title-{{ $feature->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('projects.planning.features.update', [$project, $module, $feature]) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="_planning_modal" value="edit-feature-modal-{{ $feature->id }}">
                                <div class="modal-header">
                                    <h2 class="modal-title fs-5" id="edit-feature-title-{{ $feature->id }}">Edit feature</h2>
                                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label" for="edit-feature-name-{{ $feature->id }}">Feature name</label>
                                        <input class="form-control" id="edit-feature-name-{{ $feature->id }}" name="name" value="{{ $restoreEditFeature ? old('name', $feature->name) : $feature->name }}" maxlength="180" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="edit-feature-description-{{ $feature->id }}">Description <span class="text-body-secondary">(optional)</span></label>
                                        <textarea class="form-control" id="edit-feature-description-{{ $feature->id }}" name="description" rows="3" maxlength="5000">{{ $restoreEditFeature ? old('description', $feature->description) : $feature->description }}</textarea>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <label class="form-label" for="edit-feature-priority-{{ $feature->id }}">Priority</label>
                                            <select class="form-select" id="edit-feature-priority-{{ $feature->id }}" name="priority">
                                                @foreach ($requirementPriorities as $key => $label)<option value="{{ $key }}" @selected(($restoreEditFeature ? old('priority', $feature->priority) : $feature->priority) === $key)>{{ $label }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label" for="edit-feature-status-{{ $feature->id }}">Status</label>
                                            <select class="form-select" id="edit-feature-status-{{ $feature->id }}" name="status">
                                                @foreach ($requirementStatuses as $key => $label)<option value="{{ $key }}" @selected(($restoreEditFeature ? old('status', $feature->status) : $feature->status) === $key)>{{ $label }}</option>@endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary" type="submit">Save feature</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    <div class="card mb-4" role="region" aria-labelledby="planning-roadmap-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1" id="planning-roadmap-heading">Roadmap and milestones</h2>
                <p class="small text-body-secondary mb-0">Track delivery stages, due dates, dependencies, and outcomes.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-milestone-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add milestone
            </button>
        </div>
        @if ($milestones->isEmpty())
            <div class="card-body">
                <div class="text-center rounded-3 bg-body-tertiary p-4">
                    <i class="bi bi-signpost-split fs-2 text-body-secondary" aria-hidden="true"></i>
                    <p class="fw-semibold mt-2 mb-1">No milestones planned</p>
                    <p class="small text-body-secondary mb-3">Add a dated stage or deliverable to make progress easier to follow.</p>
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-milestone-modal">Plan the first milestone</button>
                </div>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($milestones as $milestone)
                    @php
                        $milestoneStatusClass = match ($milestone->status) {
                            'done' => 'text-bg-success',
                            'in_progress' => 'text-bg-primary',
                            'blocked' => 'text-bg-danger',
                            default => 'text-bg-light border text-body',
                        };
                    @endphp
                    <article class="list-group-item p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h3 class="h6 mb-0">{{ $milestone->name }}</h3>
                                    <span class="badge {{ $milestoneStatusClass }}">{{ $milestoneStatuses[$milestone->status] ?? ucfirst($milestone->status) }}</span>
                                </div>
                                <div class="small text-body-secondary d-flex flex-wrap gap-3">
                                    @if ($milestone->starts_on || $milestone->due_on)
                                        <span><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ $milestone->starts_on?->format('M j, Y') ?? 'Start not set' }} – {{ $milestone->due_on?->format('M j, Y') ?? 'Due date not set' }}</span>
                                    @else
                                        <span><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Dates not set</span>
                                    @endif
                                    @if ($milestone->dependency)
                                        <span><i class="bi bi-arrow-return-right me-1" aria-hidden="true"></i>After {{ $milestone->dependency->name }}</span>
                                    @endif
                                </div>
                                @if ($milestone->deliverable)
                                    <p class="small mt-2 mb-0"><span class="fw-semibold">Deliverable:</span> {{ $milestone->deliverable }}</p>
                                @endif
                                @if ($milestone->description)
                                    <p class="small text-body-secondary mt-1 mb-0">{{ $milestone->description }}</p>
                                @endif
                            </div>
                            <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#edit-milestone-modal-{{ $milestone->id }}">Edit</button>
                                <form method="POST" action="{{ route('projects.planning.milestones.destroy', [$project, $milestone]) }}"
                                    onsubmit="return confirm('Remove this milestone?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove {{ $milestone->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @php
            $restoreCreateMilestone = old('_planning_modal') === 'create-milestone-modal';
        @endphp
        <div class="modal fade" id="create-milestone-modal" tabindex="-1" aria-labelledby="create-milestone-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('projects.planning.milestones.store', $project) }}">
                        @csrf
                        <input type="hidden" name="_planning_modal" value="create-milestone-modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title fs-5" id="create-milestone-title">Add milestone</h2>
                                <p class="small text-body-secondary mb-0">Add a delivery stage, target date, and expected outcome.</p>
                            </div>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="create-milestone-name">Milestone name</label>
                                    <input class="form-control" id="create-milestone-name" name="name" value="{{ $restoreCreateMilestone ? old('name') : '' }}" maxlength="180" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="create-milestone-status">Status</label>
                                    <select class="form-select" id="create-milestone-status" name="status">
                                        @foreach ($milestoneStatuses as $key => $label)<option value="{{ $key }}" @selected(($restoreCreateMilestone ? old('status', 'planned') : 'planned') === $key)>{{ $label }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="create-milestone-start">Start date</label>
                                    <input class="form-control" type="date" id="create-milestone-start" name="starts_on" value="{{ $restoreCreateMilestone ? old('starts_on') : '' }}">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="create-milestone-due">Due date</label>
                                    <input class="form-control" type="date" id="create-milestone-due" name="due_on" value="{{ $restoreCreateMilestone ? old('due_on') : '' }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="create-milestone-dependency">Depends on <span class="text-body-secondary">(optional)</span></label>
                                    <select class="form-select" id="create-milestone-dependency" name="depends_on_milestone_id">
                                        <option value="">No dependency</option>
                                        @foreach ($milestones as $candidate)<option value="{{ $candidate->id }}" @selected($restoreCreateMilestone && old('depends_on_milestone_id') == $candidate->id)>{{ $candidate->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="create-milestone-deliverable">Deliverable <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="create-milestone-deliverable" name="deliverable" rows="2" maxlength="5000">{{ $restoreCreateMilestone ? old('deliverable') : '' }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="create-milestone-description">Description <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="create-milestone-description" name="description" rows="2" maxlength="5000">{{ $restoreCreateMilestone ? old('description') : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Add milestone</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @foreach ($milestones as $milestone)
            @php
                $restoreEditMilestone = old('_planning_modal') === 'edit-milestone-modal-'.$milestone->id;
            @endphp
            <div class="modal fade" id="edit-milestone-modal-{{ $milestone->id }}" tabindex="-1" aria-labelledby="edit-milestone-title-{{ $milestone->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.planning.milestones.update', [$project, $milestone]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="_planning_modal" value="edit-milestone-modal-{{ $milestone->id }}">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="edit-milestone-title-{{ $milestone->id }}">Edit milestone</h2>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label" for="edit-milestone-name-{{ $milestone->id }}">Milestone name</label>
                                        <input class="form-control" id="edit-milestone-name-{{ $milestone->id }}" name="name" value="{{ $restoreEditMilestone ? old('name', $milestone->name) : $milestone->name }}" maxlength="180" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="edit-milestone-status-{{ $milestone->id }}">Status</label>
                                        <select class="form-select" id="edit-milestone-status-{{ $milestone->id }}" name="status">
                                            @foreach ($milestoneStatuses as $key => $label)<option value="{{ $key }}" @selected(($restoreEditMilestone ? old('status', $milestone->status) : $milestone->status) === $key)>{{ $label }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="edit-milestone-start-{{ $milestone->id }}">Start date</label>
                                        <input class="form-control" type="date" id="edit-milestone-start-{{ $milestone->id }}" name="starts_on" value="{{ $restoreEditMilestone ? old('starts_on', $milestone->starts_on?->format('Y-m-d')) : $milestone->starts_on?->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="edit-milestone-due-{{ $milestone->id }}">Due date</label>
                                        <input class="form-control" type="date" id="edit-milestone-due-{{ $milestone->id }}" name="due_on" value="{{ $restoreEditMilestone ? old('due_on', $milestone->due_on?->format('Y-m-d')) : $milestone->due_on?->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="edit-milestone-dependency-{{ $milestone->id }}">Depends on <span class="text-body-secondary">(optional)</span></label>
                                        <select class="form-select" id="edit-milestone-dependency-{{ $milestone->id }}" name="depends_on_milestone_id">
                                            <option value="">No dependency</option>
                                            @foreach ($milestones->where('id', '!=', $milestone->id) as $candidate)<option value="{{ $candidate->id }}" @selected(($restoreEditMilestone ? old('depends_on_milestone_id', $milestone->depends_on_milestone_id) : $milestone->depends_on_milestone_id) == $candidate->id)>{{ $candidate->name }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="edit-milestone-deliverable-{{ $milestone->id }}">Deliverable <span class="text-body-secondary">(optional)</span></label>
                                        <textarea class="form-control" id="edit-milestone-deliverable-{{ $milestone->id }}" name="deliverable" rows="2" maxlength="5000">{{ $restoreEditMilestone ? old('deliverable', $milestone->deliverable) : $milestone->deliverable }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="edit-milestone-description-{{ $milestone->id }}">Description <span class="text-body-secondary">(optional)</span></label>
                                        <textarea class="form-control" id="edit-milestone-description-{{ $milestone->id }}" name="description" rows="2" maxlength="5000">{{ $restoreEditMilestone ? old('description', $milestone->description) : $milestone->description }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Save milestone</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4" role="region" aria-labelledby="planning-quality-heading">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1" id="planning-quality-heading">Testing and issues</h2>
                <p class="small text-body-secondary mb-0">Keep test results and defects together, and link them to requirements when useful.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-quality-modal">
                <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add test or issue
            </button>
        </div>
        @if ($qualityItems->isEmpty())
            <div class="card-body">
                <div class="text-center rounded-3 bg-body-tertiary p-4">
                    <i class="bi bi-clipboard-check fs-2 text-body-secondary" aria-hidden="true"></i>
                    <p class="fw-semibold mt-2 mb-1">No tests or issues yet</p>
                    <p class="small text-body-secondary mb-3">Add a test case to track validation, or an issue to document a defect.</p>
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-quality-modal">Add the first record</button>
                </div>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($qualityItems as $item)
                    @php
                        $qualityStatusClass = match ($item->status) {
                            'passed', 'resolved', 'closed' => 'text-bg-success',
                            'failed', 'blocked', 'open' => 'text-bg-danger',
                            'in_progress' => 'text-bg-primary',
                            default => 'text-bg-light border text-body',
                        };
                        $qualityStatusLabel = [
                            'not_run' => 'Not run',
                            'in_progress' => 'In progress',
                        ][$item->status] ?? ucfirst(str_replace('_', ' ', $item->status));
                    @endphp
                    <article class="list-group-item p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h3 class="h6 mb-0">{{ $item->title }}</h3>
                                    <span class="badge text-bg-light border">{{ $item->kind === 'issue' ? 'Issue' : 'Test case' }}</span>
                                    <span class="badge {{ $qualityStatusClass }}">{{ $qualityStatusLabel }}</span>
                                    <span class="badge text-bg-light border">{{ ucfirst($item->priority) }} priority</span>
                                    @if ($item->severity)
                                        <span class="badge text-bg-light border">{{ ucfirst($item->severity) }} severity</span>
                                    @endif
                                </div>
                                @if ($item->project_requirement_id && $item->requirement)
                                    <p class="small text-body-secondary mb-2"><i class="bi bi-link-45deg me-1" aria-hidden="true"></i>{{ $item->requirement->code }} · {{ $item->requirement->requirement }}</p>
                                @endif
                                @if ($item->description)
                                    <p class="small mb-0">{{ $item->description }}</p>
                                @endif
                                @if ($item->expected_result || $item->actual_result || $item->resolution)
                                    <details class="small mt-2">
                                        <summary class="text-body-secondary">View results and notes</summary>
                                        <div class="row g-2 mt-1">
                                            @if ($item->expected_result)
                                                <div class="col-md-4"><span class="fw-semibold">Expected:</span> {{ $item->expected_result }}</div>
                                            @endif
                                            @if ($item->actual_result)
                                                <div class="col-md-4"><span class="fw-semibold">Actual:</span> {{ $item->actual_result }}</div>
                                            @endif
                                            @if ($item->resolution)
                                                <div class="col-md-4"><span class="fw-semibold">Resolution:</span> {{ $item->resolution }}</div>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </div>
                            <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#edit-quality-modal-{{ $item->id }}">Edit</button>
                                <form method="POST" action="{{ route('projects.planning.quality-items.destroy', [$project, $item]) }}"
                                    onsubmit="return confirm('Remove this test or issue record?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove {{ $item->title }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @php
            $restoreCreateQuality = old('_planning_modal') === 'create-quality-modal';
        @endphp
        <div class="modal fade" id="create-quality-modal" tabindex="-1" aria-labelledby="create-quality-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('projects.planning.quality-items.store', $project) }}">
                        @csrf
                        <input type="hidden" name="_planning_modal" value="create-quality-modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title fs-5" id="create-quality-title">Add test or issue</h2>
                                <p class="small text-body-secondary mb-0">Record a test case or defect and its current status.</p>
                            </div>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="quality-kind">Record type</label>
                                    <select class="form-select" id="quality-kind" name="kind">
                                        <option value="test_case" @selected(($restoreCreateQuality ? old('kind', 'test_case') : 'test_case') === 'test_case')>Test case</option>
                                        <option value="issue" @selected($restoreCreateQuality && old('kind') === 'issue')>Issue</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="quality-title">Title</label>
                                    <input class="form-control" id="quality-title" name="title" value="{{ $restoreCreateQuality ? old('title') : '' }}" maxlength="180" required>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label" for="quality-priority">Priority</label>
                                    <select class="form-select" id="quality-priority" name="priority">
                                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $key => $label)<option value="{{ $key }}" @selected(($restoreCreateQuality ? old('priority', 'medium') : 'medium') === $key)>{{ $label }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label" for="quality-severity">Severity</label>
                                    <select class="form-select" id="quality-severity" name="severity">
                                        <option value="">Not applicable</option>
                                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $key => $label)<option value="{{ $key }}" @selected($restoreCreateQuality && old('severity') === $key)>{{ $label }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label" for="quality-status">Status</label>
                                    <select class="form-select" id="quality-status" name="status">
                                        <option data-kind="test_case" value="not_run" @selected(($restoreCreateQuality ? old('status', 'not_run') : 'not_run') === 'not_run')>Not run</option>
                                        <option data-kind="test_case" value="passed" @selected($restoreCreateQuality && old('status') === 'passed')>Passed</option>
                                        <option data-kind="test_case" value="failed" @selected($restoreCreateQuality && old('status') === 'failed')>Failed</option>
                                        <option data-kind="test_case" value="blocked" @selected($restoreCreateQuality && old('status') === 'blocked')>Blocked</option>
                                        <option data-kind="issue" value="open" @selected($restoreCreateQuality && old('status') === 'open')>Open</option>
                                        <option data-kind="issue" value="in_progress" @selected($restoreCreateQuality && old('status') === 'in_progress')>In progress</option>
                                        <option data-kind="issue" value="resolved" @selected($restoreCreateQuality && old('status') === 'resolved')>Resolved</option>
                                        <option data-kind="issue" value="closed" @selected($restoreCreateQuality && old('status') === 'closed')>Closed</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="quality-description">Description or test steps <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="quality-description" name="description" rows="3">{{ $restoreCreateQuality ? old('description') : '' }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="quality-requirement">Related requirement <span class="text-body-secondary">(optional)</span></label>
                                    <select class="form-select" id="quality-requirement" name="project_requirement_id">
                                        <option value="">Unlinked</option>
                                        @foreach ($requirements as $requirement)<option value="{{ $requirement->id }}" @selected($restoreCreateQuality && old('project_requirement_id') == $requirement->id)>{{ $requirement->code }} · {{ $requirement->requirement }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="quality-expected">Expected result <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="quality-expected" name="expected_result" rows="2">{{ $restoreCreateQuality ? old('expected_result') : '' }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="quality-actual">Actual result <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="quality-actual" name="actual_result" rows="2">{{ $restoreCreateQuality ? old('actual_result') : '' }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="quality-resolution">Resolution notes <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="quality-resolution" name="resolution" rows="2" maxlength="5000">{{ $restoreCreateQuality ? old('resolution') : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Add record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @foreach ($qualityItems as $item)
            @php
                $restoreEditQuality = old('_planning_modal') === 'edit-quality-modal-'.$item->id;
            @endphp
            <div class="modal fade" id="edit-quality-modal-{{ $item->id }}" tabindex="-1" aria-labelledby="edit-quality-title-{{ $item->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('projects.planning.quality-items.update', [$project, $item]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="_planning_modal" value="edit-quality-modal-{{ $item->id }}">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="edit-quality-title-{{ $item->id }}">Edit test or issue</h2>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="quality-{{ $item->id }}-kind">Record type</label>
                                        <select class="form-select" id="quality-{{ $item->id }}-kind" name="kind">
                                            <option value="test_case" @selected(($restoreEditQuality ? old('kind', $item->kind) : $item->kind) === 'test_case')>Test case</option>
                                            <option value="issue" @selected(($restoreEditQuality ? old('kind', $item->kind) : $item->kind) === 'issue')>Issue</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="quality-{{ $item->id }}-title">Title</label>
                                        <input class="form-control" id="quality-{{ $item->id }}-title" name="title" value="{{ $restoreEditQuality ? old('title', $item->title) : $item->title }}" maxlength="180" required>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label" for="quality-{{ $item->id }}-priority">Priority</label>
                                        <select class="form-select" id="quality-{{ $item->id }}-priority" name="priority">
                                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $key => $label)<option value="{{ $key }}" @selected(($restoreEditQuality ? old('priority', $item->priority) : $item->priority) === $key)>{{ $label }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label" for="quality-{{ $item->id }}-severity">Severity</label>
                                        <select class="form-select" id="quality-{{ $item->id }}-severity" name="severity">
                                            <option value="">Not applicable</option>
                                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $key => $label)<option value="{{ $key }}" @selected(($restoreEditQuality ? old('severity', $item->severity) : $item->severity) === $key)>{{ $label }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label" for="quality-{{ $item->id }}-status">Status</label>
                                        <select class="form-select" id="quality-{{ $item->id }}-status" name="status">
                                            @foreach (['test_case' => ['not_run' => 'Not run', 'passed' => 'Passed', 'failed' => 'Failed', 'blocked' => 'Blocked'], 'issue' => ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed']] as $kind => $statuses)
                                                @foreach ($statuses as $key => $label)<option data-kind="{{ $kind }}" value="{{ $key }}" @selected(($restoreEditQuality ? old('status', $item->status) : $item->status) === $key)>{{ $label }}</option>@endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="quality-{{ $item->id }}-description">Description or test steps</label>
                                        <textarea class="form-control" id="quality-{{ $item->id }}-description" name="description" rows="3">{{ $restoreEditQuality ? old('description', $item->description) : $item->description }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="quality-{{ $item->id }}-requirement">Related requirement</label>
                                        <select class="form-select" id="quality-{{ $item->id }}-requirement" name="project_requirement_id">
                                            <option value="">Unlinked</option>
                                            @foreach ($requirements as $requirement)<option value="{{ $requirement->id }}" @selected(($restoreEditQuality ? old('project_requirement_id', $item->project_requirement_id) : $item->project_requirement_id) == $requirement->id)>{{ $requirement->code }} · {{ $requirement->requirement }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="quality-{{ $item->id }}-expected">Expected result</label>
                                        <textarea class="form-control" id="quality-{{ $item->id }}-expected" name="expected_result" rows="2">{{ $restoreEditQuality ? old('expected_result', $item->expected_result) : $item->expected_result }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="quality-{{ $item->id }}-actual">Actual result</label>
                                        <textarea class="form-control" id="quality-{{ $item->id }}-actual" name="actual_result" rows="2">{{ $restoreEditQuality ? old('actual_result', $item->actual_result) : $item->actual_result }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="quality-{{ $item->id }}-resolution">Resolution notes</label>
                                        <textarea class="form-control" id="quality-{{ $item->id }}-resolution" name="resolution" rows="2" maxlength="5000">{{ $restoreEditQuality ? old('resolution', $item->resolution) : $item->resolution }}</textarea>
                                    </div>
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
</div>
@if ($errors->any() && old('_planning_modal'))
    <script>
        (() => {
            const openInvalidModal = () => {
                const modal = document.getElementById(@json(old('_planning_modal')));
                if (modal && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', openInvalidModal, { once: true });
            } else {
                window.requestAnimationFrame(openInvalidModal);
            }
        })();
    </script>
@endif
<script>
    (() => {
        document.querySelectorAll('[id^="quality-"][id$="-kind"]').forEach((kindSelect) => {
            const form = kindSelect.closest('form');
            const statusSelect = form?.querySelector('select[name="status"]');
            if (!statusSelect) return;

            const syncStatusOptions = () => {
                const current = statusSelect.selectedOptions[0];
                statusSelect.querySelectorAll('option[data-kind]').forEach((option) => {
                    const available = option.dataset.kind === kindSelect.value;
                    option.hidden = !available;
                    option.disabled = !available;
                });
                if (!current || current.dataset.kind !== kindSelect.value) {
                    statusSelect.value = kindSelect.value === 'issue' ? 'open' : 'not_run';
                }
            };

            kindSelect.addEventListener('change', syncStatusOptions);
            syncStatusOptions();
        });
    })();
</script>
@endsection
