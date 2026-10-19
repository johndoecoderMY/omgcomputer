@extends('layouts.app')

@section('title', 'Create Meta Content')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="Create Meta Content"
        description="Compose a Facebook Page post or Instagram image/reel post."
        :back-url="route('meta-content.index')" back-label="Meta content" />
    @include('meta-content._form', [
        'post' => null,
        'action' => route('meta-content.store'),
        'method' => 'POST',
        'submitLabel' => 'Save content',
    ])
</div>
@endsection
