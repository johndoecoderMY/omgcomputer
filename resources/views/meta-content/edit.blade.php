@extends('layouts.app')

@section('title', 'Edit Planned Content')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Edit Planned Content"
        description="Update this draft or scheduled post before publishing."
        :back-url="route('meta-content.index')" back-label="Content Planner" />
    @include('meta-content._form', [
        'post' => $post,
        'action' => route('meta-content.update', $post),
        'method' => 'PUT',
        'submitLabel' => 'Save changes',
    ])
</div>
@endsection
