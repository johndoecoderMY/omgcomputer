@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization" title="Departments" description="Create and maintain the departments that teams belong to.">
        <x-slot:actions><a class="btn btn-primary" href="{{ route('departments.create') }}">Add department</a><a class="btn btn-outline-primary" href="{{ route('teams.index') }}">Manage teams</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
            data-source="{{ route('departments.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'name', 'name' => 'departments.name'],
                ['data' => 'description', 'name' => 'departments.description'],
                ['data' => 'teams_count', 'name' => 'teams_count'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Department</th><th>Description</th><th>Teams</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
