@extends('layouts.app')

@section('title', 'Add Inventory Item')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Parts & inventory" title="Add inventory item" :back-url="route('inventory.parts')" back-label="Back to parts" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('inventory.parts.store') }}">
        @csrf
        @include('inventory._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('inventory.parts') }}">Cancel</a><button class="btn btn-primary">Add item</button></div>
    </form>
</div>
@endsection
