@extends('layouts.app')

@section('title', $parent ? 'Create Subtask' : 'Create Task')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="{{ $project->name }}" :title="$parent ? 'Create subtask' : 'Create task'"
        :back-url="$parent ? route('tasks.show', $parent) : route('projects.show', $project)"
        :back-label="$parent ? 'Back to parent task' : 'Back to project'" />

    @include('tasks._form', [
        'action' => route('projects.tasks.store', $project),
        'method' => 'POST',
        'buttonLabel' => $parent ? 'Create subtask' : 'Create task',
        'cancelUrl' => $parent ? route('tasks.show', $parent) : route('projects.show', $project),
    ])
</div>
@endsection
