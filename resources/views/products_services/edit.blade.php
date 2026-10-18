@extends('layouts.app')

@section('title', 'Edit '.$product->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Catalogue" :title="'Edit '.$product->name" :back-url="route('products.show', $product)" back-label="Back to product" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')
        @include('products_services._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('products.show', $product) }}">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</div>
@endsection
