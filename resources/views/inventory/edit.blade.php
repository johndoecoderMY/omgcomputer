@extends('layouts.app')

@section('title', 'Edit '.$part->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Parts & inventory" :title="'Edit '.$part->name" :back-url="route('inventory.parts.show', $part)" back-label="Back to item" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('inventory.parts.update', $part) }}">
        @csrf
        @method('PUT')
        @include('inventory._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('inventory.parts.show', $part) }}">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</div>
@endsection
