@extends('layouts.app')

@section('title', 'Edit Website Preview')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Website portfolio" title="Edit website preview"
        description="{{ $preview->title }}"
        :back-url="route('development.website-previews.manage')" back-label="Website previews" />
    @include('website-previews._form', [
        'action' => route('development.website-previews.update', $preview),
        'method' => 'PUT',
        'buttonLabel' => 'Save changes',
    ])
</div>
@endsection
