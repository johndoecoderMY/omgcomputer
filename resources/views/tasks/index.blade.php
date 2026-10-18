@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Projects" title="Tasks"
        description="Review project tasks and subtasks in one place.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('projects.index') }}">
                <i class="fas fa-folder-open me-2"></i>Choose a project
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <form class="card card-body mb-3 p-2" method="GET" action="{{ route('tasks.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="priority">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2 col-lg-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('tasks.index') }}">Reset</a>
            </div>
        </div>
    </form>

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('tasks.datatable', request()->only(['status', 'priority'])) }}"
            data-columns="{{ json_encode([
                ['data' => 'task', 'name' => 'tasks.title'],
                ['data' => 'project_name', 'orderable' => false, 'searchable' => false],
                ['data' => 'status_label', 'name' => 'tasks.status'],
                ['data' => 'priority_label', 'name' => 'tasks.priority'],
                ['data' => 'due_date', 'name' => 'tasks.due_date'],
                ['data' => 'subtasks_count', 'name' => 'subtasks_count'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Priority</th><th>Due</th><th>Subtasks</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
    </div>
</div>
@endsection
