@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="container-fluid py-3" data-project-ajax-root>
    <x-page-header eyebrow="Workspace" title="Create project"
        :back-url="route('projects.index')" back-label="All projects" />
    @include('projects._form', [
        'action' => route('projects.store'),
        'method' => 'POST',
        'buttonLabel' => 'Create project',
    ])
</div>
@endsection
