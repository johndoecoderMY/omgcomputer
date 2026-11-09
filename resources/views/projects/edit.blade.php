@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="container-fluid py-3" data-project-ajax-root>
    <x-page-header eyebrow="Workspace" title="Edit project"
        :back-url="route('projects.show', $project)" back-label="Back to project" />
    @include('projects._form', [
        'action' => route('projects.update', $project),
        'method' => 'PUT',
        'buttonLabel' => 'Save changes',
    ])
</div>
@endsection
