@extends('layouts.app')

@section('title', 'Add Website Preview')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Website portfolio" title="Add website preview"
        description="Add a project screenshot and optional live preview link."
        :back-url="route('development.website-previews.manage')" back-label="Website previews" />
    @include('website-previews._form', [
        'action' => route('development.website-previews.store'),
        'method' => 'POST',
        'buttonLabel' => 'Add preview',
    ])
</div>
@endsection
