@extends('layouts.app')

@section('title', 'Plan Content')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Plan Content"
        description="Draft content for one or more platforms and choose when it should go out."
        :back-url="route('meta-content.index')" back-label="Content Planner" />
    @include('meta-content._form', [
        'post' => null,
        'action' => route('meta-content.store'),
        'method' => 'POST',
        'submitLabel' => 'Save content',
    ])
</div>
@endsection
