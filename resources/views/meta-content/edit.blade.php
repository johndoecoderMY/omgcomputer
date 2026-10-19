@extends('layouts.app')

@section('title', 'Edit Meta Content')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Edit Meta Content"
        description="Update this draft or scheduled post before publishing."
        :back-url="route('meta-content.index')" back-label="Meta content" />
    @include('meta-content._form', [
        'post' => $post,
        'action' => route('meta-content.update', $post),
        'method' => 'PUT',
        'submitLabel' => 'Save changes',
    ])
</div>
@endsection
