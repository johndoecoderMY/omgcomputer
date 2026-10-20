@extends('layouts.app')

@section('title', 'Teams')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization" title="Teams" description="Organize staff into department-based teams and manage team membership.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('departments.index') }}">Manage departments</a>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-team-modal">
                <i class="fas fa-plus me-2" aria-hidden="true"></i>Create team
            </button>
        </x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="card p-2">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('teams.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'name', 'name' => 'teams.name'],
                ['data' => 'department_name', 'name' => 'departments.name'],
                ['data' => 'users_count', 'name' => 'users_count'],
                ['data' => 'members', 'orderable' => false, 'searchable' => false],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Team</th><th>Department</th><th>Members</th><th>Member names</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table></div>
    </div>

    <div class="modal fade" id="create-team-modal" tabindex="-1" aria-labelledby="create-team-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('teams.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="create-team-title">Create team</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="new-team-name">Team name</label>
                            <input class="form-control" id="new-team-name" name="name" value="{{ old('name') }}" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-team-department">Department <span class="text-body-secondary">(optional)</span></label>
                            <select class="form-select" id="new-team-department" name="department_id">
                                <option value="">No department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new-team-description">Description</label>
                            <textarea class="form-control" id="new-team-description" name="description" rows="2" maxlength="2000">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new-team-members">Members</label>
                            <select class="form-select" id="new-team-members" name="user_ids[]" multiple size="5">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('user_ids', [])), true))>
                                        {{ $user->name }}{{ $user->teams->isNotEmpty() ? ' (also in '.$user->teams->pluck('name')->join(', ').')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple people. Members keep their existing team memberships.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Create team</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if ($errors->any())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('create-team-modal')).show();
            });
        </script>
    @endpush
@endif
