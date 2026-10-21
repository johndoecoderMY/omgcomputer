@extends('layouts.app')

@section('title', $parent ? 'Create Subtask' : 'Create Task')

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$project?->name ?? 'Tasks'" :title="$parent ? 'Create subtask' : 'Create task'"
        :back-url="$parent ? route('tasks.show', $parent) : ($project ? route('projects.tasks.index', $project) : route('tasks.index'))"
        :back-label="$parent ? 'Back to parent task' : ($project ? 'Back to project tasks' : 'Back to tasks')" />

    @include('tasks._form', [
        'action' => $parent && !$project
            ? route('tasks.subtasks.store', $parent)
            : ($project ? route('projects.tasks.store', $project) : route('tasks.store')),
        'method' => 'POST',
        'buttonLabel' => $parent ? 'Create subtask' : 'Create task',
        'cancelUrl' => $parent ? route('tasks.show', $parent) : ($project ? route('projects.tasks.index', $project) : route('tasks.index')),
    ])
</div>
@endsection
