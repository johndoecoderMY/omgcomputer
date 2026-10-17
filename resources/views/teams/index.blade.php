@extends('layouts.app')

@section('title', 'Teams')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization" title="Teams" description="Organize staff into department-based teams and manage team membership.">
        <x-slot:actions><a class="btn btn-outline-primary" href="{{ route('departments.index') }}">Manage departments</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    @if ($departments->isEmpty())
        <div class="alert alert-info">Create a department before creating a team. <a href="{{ route('departments.index') }}">Manage departments</a></div>
    @else
        <details class="card mb-3">
            <summary class="card-header fw-semibold">Create team</summary>
            <form method="POST" action="{{ route('teams.store') }}">
                @csrf
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label" for="new-team-name">Team name</label><input class="form-control" id="new-team-name" name="name" value="{{ old('name') }}" required maxlength="255"></div>
                    <div class="col-md-6"><label class="form-label" for="new-team-department">Department</label><select class="form-select" id="new-team-department" name="department_id" required>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label" for="new-team-description">Description</label><textarea class="form-control" id="new-team-description" name="description" rows="2" maxlength="2000"></textarea></div>
                    <div class="col-12"><label class="form-label" for="new-team-members">Members</label><select class="form-select" id="new-team-members" name="user_ids[]" multiple size="5">@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}{{ $user->teams->isNotEmpty() ? ' (also in ' . $user->teams->pluck('name')->join(', ') . ')' : '' }}</option>@endforeach</select><div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple people. Members keep their existing team memberships.</div></div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary">Create team</button></div>
            </form>
        </details>
    @endif

    <div class="card p-2">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0" data-datatable
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
</div>
@endsection
