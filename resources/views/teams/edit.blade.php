@extends('layouts.app')

@section('title', 'Edit Team')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization" title="Edit team"
        :back-url="route('teams.index')" back-label="All teams" />
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form class="card" method="POST" action="{{ route('teams.update', $team) }}">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="team-name">Team name</label><input class="form-control" id="team-name" name="name" value="{{ old('name', $team->name) }}" required maxlength="255"></div>
            <div class="col-md-6"><label class="form-label" for="team-department">Department</label><select class="form-select" id="team-department" name="department_id" required>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((int) old('department_id', $team->department_id) === $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="team-description">Description</label><textarea class="form-control" id="team-description" name="description" rows="2" maxlength="2000">{{ old('description', $team->description) }}</textarea></div>
            <div class="col-12"><label class="form-label" for="team-members">Members</label><select class="form-select" id="team-members" name="user_ids[]" multiple size="8">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(in_array($user->id, old('user_ids', $team->users->modelKeys()), true) || in_array((string) $user->id, old('user_ids', []), true))>{{ $user->name }}{{ $user->team && $user->team_id !== $team->id ? ' (currently ' . $user->team->name . ')' : '' }}</option>@endforeach</select><div class="form-text">Selecting someone assigned elsewhere moves them here. Clear all selections to leave the team empty.</div></div>
        </div>
        <div class="card-footer bg-body d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="{{ route('teams.index') }}">Cancel</a>
            <button class="btn btn-primary">Save team</button>
        </div>
    </form>
    <form class="mt-3" method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team? Its members will become unassigned.');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete team</button></form>
</div>
@endsection
