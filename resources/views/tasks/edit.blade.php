@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$project?->name ?? 'Tasks'" title="Edit task"
        :back-url="route('tasks.show', $parent ?? $task)" back-label="Back to task" />

    @include('tasks._form', [
        'action' => route('tasks.update', $task),
        'method' => 'PUT',
        'buttonLabel' => 'Save changes',
        'cancelUrl' => route('tasks.show', $parent ?? $task),
    ])
</div>
@endsection
