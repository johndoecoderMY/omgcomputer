@extends('layouts.app')

@section('title', $department->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Members & organization / Departments" :title="$department->name" :back-url="route('departments.index')" back-label="All departments">
        <x-slot:actions><a class="btn btn-outline-primary" href="{{ route('departments.edit', $department) }}">Edit</a><a class="btn btn-outline-danger" href="{{ route('departments.delete', $department) }}">Delete</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-3"><div class="card-body"><div class="small text-body-secondary mb-1">Description</div><p class="mb-0">{{ $department->description ?: 'No description provided.' }}</p></div></div>
    <div class="card"><div class="card-header fw-semibold">Teams ({{ $department->teams->count() }})</div>
        <ul class="list-group list-group-flush">@forelse ($department->teams as $team)<li class="list-group-item d-flex justify-content-between"><span>{{ $team->name }}</span><a href="{{ route('teams.edit', $team) }}">View / edit team</a></li>@empty<li class="list-group-item text-body-secondary">No teams are assigned to this department.</li>@endforelse</ul>
    </div>
</div>
@endsection
