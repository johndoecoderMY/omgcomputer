@extends('layouts.app')

@section('title', 'Planning · '.$project->name)

@section('content')
<div class="container-fluid py-3" data-project-ajax-root>
    <x-page-header eyebrow="Project planning" :title="$project->name"
        description="Define the project brief, requirements, modules, delivery milestones, and validation plan."
        :back-url="route('projects.show', $project)" back-label="Back to project">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('projects.tasks.index', $project) }}">
                <i class="bi bi-list-task me-2" aria-hidden="true"></i>Development board
            </a>
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
            ['Status', ucfirst(str_replace('_', ' ', $project->planning_status)), 'text-primary'],
            ['Requirements', $requirements->count(), 'text-body'],
            ['Modules & features', $modules->count().' / '.$modules->sum(fn ($module) => $module->features->count()), 'text-body'],
            ['Milestones', $milestones->count(), 'text-body'],
            ['Health', $projectHealth, $projectHealth === 'At risk' ? 'text-danger' : 'text-success'],
        ] as [$label, $value, $color])
            <div class="col-6 col-xl"><div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary text-uppercase fw-semibold">{{ $label }}</div>
                <div class="h4 mb-0 {{ $color }}">{{ $value }}</div>
            </div></div></div>
        @endforeach
    </div>
    <p class="small text-body-secondary mb-4">Health is marked at risk when a milestone is overdue or a high/critical issue remains unresolved.</p>

    <section class="card mb-4" aria-labelledby="planning-brief-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1" id="planning-brief-heading">Project brief</h2>
            <p class="small text-body-secondary mb-0">Capture the business context, outcomes, boundaries, and delivery targets.</p>
        </div>
        <form method="POST" action="{{ route('projects.planning.overview.update', $project) }}" class="card-body">
            @csrf @method('PUT')
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
                    <div class="col-lg-6">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <textarea class="form-control" id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="10000">{{ old($field, $value) }}</textarea>
                    </div>
                @endforeach
                <div class="col-md-4">
                    <label class="form-label" for="planning_status">Project status</label>
                    <select class="form-select" id="planning_status" name="planning_status" required>
                        @foreach (['draft' => 'Draft', 'planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('planning_status', $project->planning_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="start_date">Start date</label>
                    <input class="form-control" type="date" id="start_date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="target_end_date">Target end date</label>
                    <input class="form-control" type="date" id="target_end_date" name="target_end_date" value="{{ old('target_end_date', $project->target_end_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="budget">Budget (RM)</label>
                    <input class="form-control" type="number" step="0.01" min="0" id="budget" name="budget" value="{{ old('budget', $project->budget) }}">
                </div>
            </div>
            <div class="text-end mt-3"><button class="btn btn-primary" type="submit">Save project brief</button></div>
        </form>
    </section>

    <section class="card mb-4" aria-labelledby="planning-requirements-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1" id="planning-requirements-heading">Requirements</h2>
            <p class="small text-body-secondary mb-0">Track BR, FR, and NFR items, priority, user stories, acceptance criteria, and implementation status.</p>
        </div>
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('projects.planning.requirements.store', $project) }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3"><label class="form-label" for="requirement">Requirement</label><input class="form-control" id="requirement" name="requirement" maxlength="500" required></div>
                    <div class="col-sm-4 col-lg-2"><label class="form-label" for="requirement_type">Type</label><select class="form-select" id="requirement_type" name="requirement_type">@foreach ($requirementTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-sm-4 col-lg-2"><label class="form-label" for="requirement_priority">Priority</label><select class="form-select" id="requirement_priority" name="priority">@foreach ($requirementPriorities as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-sm-4 col-lg-2"><label class="form-label" for="requirement_status">Status</label><select class="form-select" id="requirement_status" name="status">@foreach ($requirementStatuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-lg-3"><label class="form-label" for="requirement_module">Module</label><select class="form-select" id="requirement_module" name="project_module_id"><option value="">Unassigned</option>@foreach ($modules as $module)<option value="{{ $module->id }}">{{ $module->name }}</option>@endforeach</select></div>
                    <div class="col-lg-3"><label class="form-label" for="requirement_parent">Parent requirement</label><select class="form-select" id="requirement_parent" name="parent_requirement_id"><option value="">None</option>@foreach ($requirements as $parentRequirement)<option value="{{ $parentRequirement->id }}">{{ $parentRequirement->code }} · {{ $parentRequirement->requirement }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label" for="user_story">User story <span class="text-body-secondary">(optional)</span></label><textarea class="form-control" id="user_story" name="user_story" rows="2" placeholder="As a [user], I want [capability] so that [outcome]."></textarea></div>
                    <div class="col-md-6"><label class="form-label" for="acceptance_criteria">Acceptance criteria <span class="text-body-secondary">(optional)</span></label><textarea class="form-control" id="acceptance_criteria" name="acceptance_criteria" rows="2" placeholder="One testable criterion per line"></textarea></div>
                </div>
                <div class="text-end mt-2"><button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus me-1" aria-hidden="true"></i>Add requirement</button></div>
            </form>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($requirements as $requirement)
                <article class="list-group-item p-3">
                    <form method="POST" action="{{ route('projects.planning.requirements.update', [$project, $requirement]) }}">
                        @csrf @method('PUT')
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-3"><label class="form-label small" for="req-{{ $requirement->id }}-text">{{ $requirement->code ?: 'Requirement' }}</label><input class="form-control" id="req-{{ $requirement->id }}-text" name="requirement" value="{{ $requirement->requirement }}" maxlength="500" required></div>
                            <div class="col-sm-4 col-lg-2"><label class="form-label small" for="req-{{ $requirement->id }}-type">Type</label><select class="form-select" id="req-{{ $requirement->id }}-type" name="requirement_type">@foreach ($requirementTypes as $key => $label)<option value="{{ $key }}" @selected($requirement->requirement_type === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-sm-4 col-lg-2"><label class="form-label small" for="req-{{ $requirement->id }}-priority">Priority</label><select class="form-select" id="req-{{ $requirement->id }}-priority" name="priority">@foreach ($requirementPriorities as $key => $label)<option value="{{ $key }}" @selected($requirement->priority === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-sm-4 col-lg-2"><label class="form-label small" for="req-{{ $requirement->id }}-status">Status</label><select class="form-select" id="req-{{ $requirement->id }}-status" name="status">@foreach ($requirementStatuses as $key => $label)<option value="{{ $key }}" @selected($requirement->status === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-lg-3"><label class="form-label small" for="req-{{ $requirement->id }}-module">Module</label><select class="form-select" id="req-{{ $requirement->id }}-module" name="project_module_id"><option value="">Unassigned</option>@foreach ($modules as $module)<option value="{{ $module->id }}" @selected($requirement->project_module_id === $module->id)>{{ $module->name }}</option>@endforeach</select></div>
                            <div class="col-lg-3"><label class="form-label small" for="req-{{ $requirement->id }}-parent">Parent requirement</label><select class="form-select" id="req-{{ $requirement->id }}-parent" name="parent_requirement_id"><option value="">None</option>@foreach ($requirements->where('id', '!=', $requirement->id) as $parentRequirement)<option value="{{ $parentRequirement->id }}" @selected($requirement->parent_requirement_id === $parentRequirement->id)>{{ $parentRequirement->code }} · {{ $parentRequirement->requirement }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label small" for="req-{{ $requirement->id }}-story">User story</label><textarea class="form-control" id="req-{{ $requirement->id }}-story" name="user_story" rows="2">{{ $requirement->user_story }}</textarea></div>
                            <div class="col-md-6"><label class="form-label small" for="req-{{ $requirement->id }}-criteria">Acceptance criteria</label><textarea class="form-control" id="req-{{ $requirement->id }}-criteria" name="acceptance_criteria" rows="2">{{ $requirement->acceptance_criteria }}</textarea></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="small text-body-secondary">{{ $requirement->tasks->count() }} linked tasks</span>
                            <div class="d-flex gap-2"><button class="btn btn-sm btn-outline-primary" type="submit">Save</button></div>
                        </div>
                    </form>
                    <form class="text-end mt-1" method="POST" action="{{ route('projects.planning.requirements.destroy', [$project, $requirement]) }}" onsubmit="return confirm('Remove this requirement? Linked task references will be cleared.');">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                    </form>
                </article>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No structured requirements yet.</p>
            @endforelse
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="planning-modules-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1" id="planning-modules-heading">Modules and features</h2>
            <p class="small text-body-secondary mb-0">Break requirements into the system areas and user-facing capabilities to deliver.</p>
        </div>
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('projects.planning.modules.store', $project) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label" for="module-name">Module name</label><input class="form-control" id="module-name" name="name" maxlength="180" required></div>
                <div class="col-md-6"><label class="form-label" for="module-description">Description</label><input class="form-control" id="module-description" name="description" maxlength="5000"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Add module</button></div>
            </form>
        </div>
        <div class="card-body">
            @forelse ($modules as $module)
                <article class="border rounded p-3 mb-3">
                    <form method="POST" action="{{ route('projects.planning.modules.update', [$project, $module]) }}" class="row g-2 align-items-end">
                        @csrf @method('PUT')
                        <div class="col-md-4"><label class="form-label" for="module-{{ $module->id }}">Module</label><input class="form-control" id="module-{{ $module->id }}" name="name" value="{{ $module->name }}" maxlength="180" required></div>
                        <div class="col-md-6"><label class="form-label" for="module-{{ $module->id }}-description">Description</label><input class="form-control" id="module-{{ $module->id }}-description" name="description" value="{{ $module->description }}" maxlength="5000"></div>
                        <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Save module</button></div>
                    </form>
                    <form class="text-end mt-2" method="POST" action="{{ route('projects.planning.modules.destroy', [$project, $module]) }}" onsubmit="return confirm('Remove this module and its features?');">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Remove module</button>
                    </form>
                    <div class="mt-3 ps-md-3 border-start">
                        <h3 class="h6">Features <span class="badge text-bg-light border">{{ $module->features->count() }}</span></h3>
                        @foreach ($module->features as $feature)
                            <form method="POST" action="{{ route('projects.planning.features.update', [$project, $module, $feature]) }}" class="row g-2 align-items-end mb-2">
                                @csrf @method('PUT')
                                <div class="col-md-4"><label class="visually-hidden" for="feature-{{ $feature->id }}">Feature name</label><input class="form-control" id="feature-{{ $feature->id }}" name="name" value="{{ $feature->name }}" maxlength="180" required></div>
                                <div class="col-md-3"><label class="visually-hidden" for="feature-{{ $feature->id }}-priority">Priority</label><select class="form-select" id="feature-{{ $feature->id }}-priority" name="priority">@foreach ($requirementPriorities as $key => $label)<option value="{{ $key }}" @selected($feature->priority === $key)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-md-3"><label class="visually-hidden" for="feature-{{ $feature->id }}-status">Status</label><select class="form-select" id="feature-{{ $feature->id }}-status" name="status">@foreach ($requirementStatuses as $key => $label)<option value="{{ $key }}" @selected($feature->status === $key)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-md-2 d-flex gap-2"><button class="btn btn-sm btn-outline-primary" type="submit">Save</button></div>
                                <div class="col-12"><label class="visually-hidden" for="feature-{{ $feature->id }}-description">Feature description</label><input class="form-control form-control-sm" id="feature-{{ $feature->id }}-description" name="description" value="{{ $feature->description }}" maxlength="5000" placeholder="Feature description (optional)"></div>
                            </form>
                            <form class="text-end mb-2" method="POST" action="{{ route('projects.planning.features.destroy', [$project, $module, $feature]) }}" onsubmit="return confirm('Remove this feature?');">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger" type="submit">Remove feature</button></form>
                        @endforeach
                        <form method="POST" action="{{ route('projects.planning.features.store', [$project, $module]) }}" class="row g-2 mt-2">
                            @csrf
                            <div class="col-md-4"><label class="visually-hidden" for="new-feature-{{ $module->id }}">New feature</label><input class="form-control form-control-sm" id="new-feature-{{ $module->id }}" name="name" maxlength="180" placeholder="New feature name" required></div>
                            <div class="col-md-3"><select class="form-select form-select-sm" name="priority" aria-label="Feature priority">@foreach ($requirementPriorities as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-3"><select class="form-select form-select-sm" name="status" aria-label="Feature status">@foreach ($requirementStatuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary" type="submit">Add feature</button></div>
                            <div class="col-12"><input class="form-control form-control-sm" name="description" maxlength="5000" placeholder="Feature description (optional)"></div>
                        </form>
                    </div>
                </article>
            @empty
                <p class="text-body-secondary mb-0">No modules added yet.</p>
            @endforelse
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="planning-roadmap-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1" id="planning-roadmap-heading">Roadmap and milestones</h2>
            <p class="small text-body-secondary mb-0">Set phases, dates, deliverables, status, and milestone dependencies.</p>
        </div>
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('projects.planning.milestones.store', $project) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><label class="form-label" for="milestone-name">Milestone</label><input class="form-control" id="milestone-name" name="name" maxlength="180" required></div>
                <div class="col-md-2"><label class="form-label" for="milestone-start">Start</label><input class="form-control" id="milestone-start" type="date" name="starts_on"></div>
                <div class="col-md-2"><label class="form-label" for="milestone-due">Due</label><input class="form-control" id="milestone-due" type="date" name="due_on"></div>
                <div class="col-md-2"><label class="form-label" for="milestone-status">Status</label><select class="form-select" id="milestone-status" name="status">@foreach ($milestoneStatuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label" for="milestone-dependency">Depends on</label><select class="form-select" id="milestone-dependency" name="depends_on_milestone_id"><option value="">No dependency</option>@foreach ($milestones as $milestone)<option value="{{ $milestone->id }}">{{ $milestone->name }}</option>@endforeach</select></div>
                <div class="col-md-5"><label class="form-label" for="milestone-deliverable">Deliverable</label><input class="form-control" id="milestone-deliverable" name="deliverable" maxlength="5000"></div>
                <div class="col-md-5"><label class="form-label" for="milestone-description">Description</label><input class="form-control" id="milestone-description" name="description" maxlength="5000"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Add milestone</button></div>
            </form>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($milestones as $milestone)
                <form method="POST" action="{{ route('projects.planning.milestones.update', [$project, $milestone]) }}" class="list-group-item p-3">
                    @csrf @method('PUT')
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label small" for="milestone-{{ $milestone->id }}">Milestone</label><input class="form-control" id="milestone-{{ $milestone->id }}" name="name" value="{{ $milestone->name }}" maxlength="180" required></div>
                        <div class="col-md-2"><label class="form-label small" for="milestone-{{ $milestone->id }}-start">Start</label><input class="form-control" id="milestone-{{ $milestone->id }}-start" type="date" name="starts_on" value="{{ $milestone->starts_on?->format('Y-m-d') }}"></div>
                        <div class="col-md-2"><label class="form-label small" for="milestone-{{ $milestone->id }}-due">Due</label><input class="form-control" id="milestone-{{ $milestone->id }}-due" type="date" name="due_on" value="{{ $milestone->due_on?->format('Y-m-d') }}"></div>
                        <div class="col-md-2"><label class="form-label small" for="milestone-{{ $milestone->id }}-status">Status</label><select class="form-select" id="milestone-{{ $milestone->id }}-status" name="status">@foreach ($milestoneStatuses as $key => $label)<option value="{{ $key }}" @selected($milestone->status === $key)>{{ $label }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small" for="milestone-{{ $milestone->id }}-dependency">Depends on</label><select class="form-select" id="milestone-{{ $milestone->id }}-dependency" name="depends_on_milestone_id"><option value="">No dependency</option>@foreach ($milestones->where('id', '!=', $milestone->id) as $candidate)<option value="{{ $candidate->id }}" @selected($milestone->depends_on_milestone_id === $candidate->id)>{{ $candidate->name }}</option>@endforeach</select></div>
                        <div class="col-md-5"><label class="form-label small" for="milestone-{{ $milestone->id }}-deliverable">Deliverable</label><input class="form-control" id="milestone-{{ $milestone->id }}-deliverable" name="deliverable" value="{{ $milestone->deliverable }}" maxlength="5000"></div>
                        <div class="col-md-5"><label class="form-label small" for="milestone-{{ $milestone->id }}-description">Description</label><input class="form-control" id="milestone-{{ $milestone->id }}-description" name="description" value="{{ $milestone->description }}" maxlength="5000"></div>
                        <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Save milestone</button></div>
                    </div>
                </form>
                <form class="list-group-item text-end py-1" method="POST" action="{{ route('projects.planning.milestones.destroy', [$project, $milestone]) }}" onsubmit="return confirm('Remove this milestone?');">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger" type="submit">Remove milestone</button></form>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No milestones planned yet.</p>
            @endforelse
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="planning-quality-heading">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1" id="planning-quality-heading">Testing and issues</h2>
            <p class="small text-body-secondary mb-0">Record test cases and link defects to requirements, with severity, status, and resolution notes.</p>
        </div>
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('projects.planning.quality-items.store', $project) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2"><label class="form-label" for="quality-kind">Type</label><select class="form-select" id="quality-kind" name="kind"><option value="test_case">Test case</option><option value="issue">Issue</option></select></div>
                <div class="col-md-4"><label class="form-label" for="quality-title">Title</label><input class="form-control" id="quality-title" name="title" maxlength="180" required></div>
                <div class="col-md-2"><label class="form-label" for="quality-priority">Priority</label><select class="form-select" id="quality-priority" name="priority">@foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="quality-severity">Severity</label><select class="form-select" id="quality-severity" name="severity"><option value="">Not applicable</option>@foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="quality-status">Status</label><select class="form-select" id="quality-status" name="status"><option data-kind="test_case" value="not_run">Not run</option><option data-kind="test_case" value="passed">Passed</option><option data-kind="test_case" value="failed">Failed</option><option data-kind="test_case" value="blocked">Blocked</option><option data-kind="issue" value="open">Open</option><option data-kind="issue" value="in_progress">In progress</option><option data-kind="issue" value="resolved">Resolved</option><option data-kind="issue" value="closed">Closed</option></select></div>
                <div class="col-md-6"><label class="form-label" for="quality-description">Description / steps</label><textarea class="form-control" id="quality-description" name="description" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label" for="quality-requirement">Related requirement</label><select class="form-select" id="quality-requirement" name="project_requirement_id"><option value="">Unlinked</option>@foreach ($requirements as $requirement)<option value="{{ $requirement->id }}">{{ $requirement->code }} · {{ $requirement->requirement }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="quality-expected">Expected result</label><textarea class="form-control" id="quality-expected" name="expected_result" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label" for="quality-actual">Actual result</label><textarea class="form-control" id="quality-actual" name="actual_result" rows="2"></textarea></div>
                <div class="col-md-10"><label class="form-label" for="quality-resolution">Resolution notes</label><input class="form-control" id="quality-resolution" name="resolution" maxlength="5000"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Add record</button></div>
            </form>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($qualityItems as $item)
                <article class="list-group-item p-3">
                    <form method="POST" action="{{ route('projects.planning.quality-items.update', [$project, $item]) }}">
                        @csrf @method('PUT')
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2"><label class="form-label small" for="quality-{{ $item->id }}-kind">Type</label><select class="form-select" id="quality-{{ $item->id }}-kind" name="kind"><option value="test_case" @selected($item->kind === 'test_case')>Test case</option><option value="issue" @selected($item->kind === 'issue')>Issue</option></select></div>
                            <div class="col-md-4"><label class="form-label small" for="quality-{{ $item->id }}-title">Title</label><input class="form-control" id="quality-{{ $item->id }}-title" name="title" value="{{ $item->title }}" maxlength="180" required></div>
                            <div class="col-md-2"><label class="form-label small" for="quality-{{ $item->id }}-priority">Priority</label><select class="form-select" id="quality-{{ $item->id }}-priority" name="priority">@foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $key => $label)<option value="{{ $key }}" @selected($item->priority === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label class="form-label small" for="quality-{{ $item->id }}-severity">Severity</label><select class="form-select" id="quality-{{ $item->id }}-severity" name="severity"><option value="">N/A</option>@foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $key => $label)<option value="{{ $key }}" @selected($item->severity === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label class="form-label small" for="quality-{{ $item->id }}-status">Status</label><select class="form-select" id="quality-{{ $item->id }}-status" name="status">
                                @foreach (['test_case' => ['not_run' => 'Not run', 'passed' => 'Passed', 'failed' => 'Failed', 'blocked' => 'Blocked'], 'issue' => ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed']] as $kind => $statuses)
                                    @foreach ($statuses as $key => $label)<option data-kind="{{ $kind }}" value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>@endforeach
                                @endforeach
                            </select></div>
                            <div class="col-md-6"><label class="form-label small" for="quality-{{ $item->id }}-description">Description / steps</label><textarea class="form-control" id="quality-{{ $item->id }}-description" name="description" rows="2">{{ $item->description }}</textarea></div>
                            <div class="col-md-6"><label class="form-label small" for="quality-{{ $item->id }}-requirement">Related requirement</label><select class="form-select" id="quality-{{ $item->id }}-requirement" name="project_requirement_id"><option value="">Unlinked</option>@foreach ($requirements as $requirement)<option value="{{ $requirement->id }}" @selected($item->project_requirement_id === $requirement->id)>{{ $requirement->code }} · {{ $requirement->requirement }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label small" for="quality-{{ $item->id }}-expected">Expected result</label><textarea class="form-control" id="quality-{{ $item->id }}-expected" name="expected_result" rows="2">{{ $item->expected_result }}</textarea></div>
                            <div class="col-md-6"><label class="form-label small" for="quality-{{ $item->id }}-actual">Actual result</label><textarea class="form-control" id="quality-{{ $item->id }}-actual" name="actual_result" rows="2">{{ $item->actual_result }}</textarea></div>
                            <div class="col-md-10"><label class="form-label small" for="quality-{{ $item->id }}-resolution">Resolution</label><input class="form-control" id="quality-{{ $item->id }}-resolution" name="resolution" value="{{ $item->resolution }}" maxlength="5000"></div>
                            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Save record</button></div>
                        </div>
                    </form>
                    <form class="text-end mt-2" method="POST" action="{{ route('projects.planning.quality-items.destroy', [$project, $item]) }}" onsubmit="return confirm('Remove this test or issue record?');">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger" type="submit">Remove</button></form>
                </article>
            @empty
                <p class="list-group-item text-body-secondary mb-0">No tests or issues recorded yet.</p>
            @endforelse
        </div>
    </section>
</div>
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
