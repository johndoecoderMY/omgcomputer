@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Workspace" title="Projects"
        description="Each project has its own progress, tasks, and subtasks.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('projects.create') }}">
                <i class="fas fa-plus me-2"></i>New project
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        @forelse ($projects as $project)
            <div class="col-12 col-xl-6">
                <article class="card h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-1">
                            <a class="text-decoration-none" href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                        </h2>
                        <p class="text-body-secondary">{{ $project->description ?: 'No description provided.' }}</p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-body-secondary">Progress</span>
                            <strong>{{ $project->progress }}%</strong>
                        </div>
                        <div class="progress mb-2" role="progressbar" aria-label="{{ $project->name }} progress"
                            aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: {{ $project->progress }}%"></div>
                        </div>
                        <p class="small text-body-secondary mb-0">
                            {{ $project->completed_tasks_count }} of {{ $project->tasks_count }} tasks complete
                        </p>
                    </div>
                    <div class="card-footer bg-body d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('projects.show', $project) }}">View project</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('projects.edit', $project) }}">Edit</a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-folder-open text-primary fs-2 mb-3" aria-hidden="true"></i>
                        <h2 class="h5">No projects yet</h2>
                        <p class="text-body-secondary">Create a project to organize its tasks and subtasks.</p>
                        <a class="btn btn-primary" href="{{ route('projects.create') }}">Create a project</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
