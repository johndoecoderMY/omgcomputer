@extends('layouts.app')

@section('title', 'Delete '.$department->name)

@section('content')
<div class="container py-3">
    <x-page-header eyebrow="Members & organization / Departments" title="Delete department" :back-url="route('departments.show', $department)" back-label="Cancel" />
    <div class="card border-danger"><div class="card-body">
        @if ($department->teams->isNotEmpty())
            <div class="alert alert-warning">This department still has teams. Move or delete those teams before deleting the department.</div>
            <ul class="mb-0">@foreach ($department->teams as $team)<li><a href="{{ route('teams.edit', $team) }}">{{ $team->name }}</a></li>@endforeach</ul>
            <div class="d-flex justify-content-end mt-3"><a class="btn btn-outline-secondary" href="{{ route('departments.show', $department) }}">Back to department</a></div>
        @else
            <p>Delete <strong>{{ $department->name }}</strong>? This action cannot be undone.</p>
            <form method="POST" action="{{ route('departments.destroy', $department) }}" class="d-flex justify-content-end gap-2">
                @csrf
                @method('DELETE')
                <a class="btn btn-outline-secondary" href="{{ route('departments.show', $department) }}">Cancel</a>
                <button class="btn btn-danger">Delete department</button>
            </form>
        @endif
    </div></div>
</div>
@endsection
