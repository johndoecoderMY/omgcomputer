@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Organization" title="Departments" description="Create and maintain the departments that teams belong to.">
        <x-slot:actions><a class="btn btn-outline-primary" href="{{ route('teams.index') }}">Manage teams</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form class="card mb-3" method="POST" action="{{ route('departments.store') }}">
        @csrf
        <div class="card-header fw-semibold">Add department</div>
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label" for="department-name">Department name</label><input class="form-control" id="department-name" name="name" value="{{ old('name') }}" required maxlength="255"></div>
            <div class="col-md-8"><label class="form-label" for="department-description">Description</label><input class="form-control" id="department-description" name="description" maxlength="2000"></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-primary">Add department</button></div>
    </form>

    <div class="card"><div class="table-responsive">
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
