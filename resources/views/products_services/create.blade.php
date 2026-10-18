@extends('layouts.app')

@section('title', 'Add Product or Service')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Catalogue" title="Add product or service" :back-url="route('products.index')" back-label="Back to catalogue" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('products.store') }}">
        @csrf
        @include('products_services._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancel</a><button class="btn btn-primary">Create</button></div>
    </form>
</div>
@endsection
