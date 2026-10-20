@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="{{ $customerView ? 'My workspace' : 'Workspace' }}" title="{{ $customerView ? 'My projects' : 'Projects' }}"
        description="{{ $customerView ? 'Projects associated with your customer profile or repair tickets.' : 'Each project has its own progress, tasks, and subtasks.' }}">
        @unless ($customerView)
            <x-slot:actions>
                <a class="btn btn-primary" href="{{ route('projects.create') }}">
                    <i class="fas fa-plus me-2"></i>New project
                </a>
            </x-slot:actions>
        @endunless
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
                data-source="{{ route('projects.datatable') }}"
                data-columns="{{ json_encode([
                    ['data' => 'name', 'name' => 'projects.name'],
                    ['data' => 'description', 'name' => 'projects.description'],
                    ...($customerView ? [] : [
                        ['data' => 'tasks_count', 'name' => 'tasks_count'],
                        ['data' => 'assignees', 'orderable' => false, 'searchable' => false],
                        ['data' => 'progress', 'orderable' => false, 'searchable' => false],
                    ]),
                    ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead>
                    <tr>
                        <th>Project</th><th>Description</th>
                        @unless ($customerView)<th>Tasks</th><th>Assigned to</th><th>Progress</th>@endunless
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
