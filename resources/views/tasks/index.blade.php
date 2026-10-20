@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Projects" title="Tasks"
        description="Review project tasks and subtasks in one place.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('tasks.create') }}">
                <i class="fas fa-plus me-2"></i>Add task
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card card-body mb-3 p-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="task-status">Status</label>
                <select class="form-select" id="task-status" data-datatable-filter>
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="task-priority">Priority</label>
                <select class="form-select" id="task-priority" data-datatable-filter>
                    <option value="">All priorities</option>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-3">
                <label class="form-label" for="task-search">Search tasks</label>
                <input class="form-control" id="task-search" type="search" data-datatable-search
                    placeholder="Search tasks, projects, or assignees">
            </div>
        </div>
    </div>

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('tasks.datatable') }}"
            data-datatable-filters="{{ json_encode([
                ['name' => 'status', 'selector' => '#task-status'],
                ['name' => 'priority', 'selector' => '#task-priority'],
            ]) }}"
            data-datatable-search="#task-search"
            data-columns="{{ json_encode([
                ['data' => 'task', 'name' => 'tasks.title'],
                ['data' => 'project_name', 'orderable' => false, 'searchable' => false],
                ['data' => 'assignees', 'orderable' => false, 'searchable' => false],
                ['data' => 'status_label', 'name' => 'tasks.status'],
                ['data' => 'priority_label', 'name' => 'tasks.priority'],
                ['data' => 'due_date', 'name' => 'tasks.due_date'],
                ['data' => 'subtasks_count', 'name' => 'subtasks_count'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Task</th><th>Project</th><th>Assigned to</th><th>Status</th><th>Priority</th><th>Due</th><th>Subtasks</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
    </div>
</div>
@endsection
