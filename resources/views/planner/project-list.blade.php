@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="container-fluid py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Projects</li></ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div><p class="text-primary fw-bold text-uppercase fs-10 mb-1">Workspace</p><h1 class="h2 mb-1">Projects</h1><p class="text-700 mb-0">Each project has its own overview, task list, Kanban board, and discussion.</p></div>
            <div class="d-flex gap-2">
                <a class="btn btn-falcon-default" href="{{ route('planner.projects') }}"><span class="fas fa-home me-2"></span>This project</a>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createProjectModal"><span class="fas fa-plus me-2"></span>New project</button>
            </div>
        </div>

        @if (session('status'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h2 class="h5 mb-1">Project workspaces</h2><p class="text-600 fs-10 mb-0">Tasks and progress are kept within each project.</p></div>
                <span class="badge badge-subtle-primary">{{ $projects->count() }} projects</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 fs-10" data-datatables='{"pageLength":10,"language":{"emptyTable":"No projects yet. Create a workspace to get started."}}'>
                    <thead class="bg-body-tertiary"><tr><th class="ps-3">Project</th><th>Owner</th><th>Access</th><th>Tasks complete</th><th>Progress</th><th>Workspace</th></tr></thead>
                    <tbody>
                        @foreach ($projects as $project)
                            @php
                                $done = (int) $project->completed_tasks_count;
                                $total = (int) $project->tasks_count;
                                $percent = $total === 0 ? 0 : (int) round($done / $total * 100);
                            @endphp
                            <tr>
                                <td class="ps-3"><a class="fw-semi-bold" href="{{ route('projects.show', $project) }}">{{ $project->name }}</a><div class="text-600">{{ $project->description }}</div></td>
                                <td>{{ $project->owner->name }}</td>
                                <td><span class="badge rounded-pill badge-subtle-{{ $project->visibility === 'team' ? 'success' : 'secondary' }}">{{ $project->visibility === 'team' ? 'Team' : 'Private' }}</span></td>
                                <td>{{ $done }} / {{ $total }}</td>
                                <td><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-success" style="width:{{ $percent }}%"></div></div><span>{{ $percent }}%</span></div></td>
                                <td class="text-nowrap"><a class="btn btn-sm btn-falcon-default" href="{{ route('projects.show', $project) }}">Overview</a> <a class="btn btn-sm btn-falcon-default" href="{{ route('planner.index', ['project' => $project->id, 'view' => 'list']) }}">Tasks</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('projects.store') }}">
                @csrf
                <input type="hidden" name="ui_modal" value="createProjectModal">
                <div class="modal-header"><h2 class="modal-title fs-5" id="createProjectModalLabel">Create project</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label" for="project-name">Project name</label><input class="form-control" id="project-name" name="name" value="{{ old('name') }}" maxlength="180" required></div>
                    <div class="mb-3"><label class="form-label" for="project-description">Description</label><textarea class="form-control" id="project-description" name="description" rows="3" maxlength="3000" placeholder="What work belongs in this project?">{{ old('description') }}</textarea></div>
                    <div><label class="form-label" for="project-visibility">Access</label><select class="form-select" id="project-visibility" name="visibility" required><option value="team" @selected(old('visibility', 'team') === 'team')>Shared with team</option><option value="personal" @selected(old('visibility') === 'personal')>Private</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-falcon-default" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit"><span class="fas fa-plus me-2"></span>Create project</button></div>
            </form>
        </div></div>
    </div>
    @if ($errors->any())
        @push('scripts')<script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>@endpush
    @endif
@endsection
