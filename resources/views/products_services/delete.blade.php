@extends('layouts.app')

@section('title', 'Delete '.$product->name)

@section('content')
<div class="container py-3">
    <x-page-header eyebrow="Catalogue" title="Delete product or service" :back-url="route('products.show', $product)" back-label="Cancel" />
    <div class="card border-danger"><div class="card-body">
        <p>Delete <strong>{{ $product->name }}</strong> from the active catalogue? Existing bookings and billing records keep their saved descriptions.</p>
        <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-flex justify-content-end gap-2">
            @csrf
            @method('DELETE')
            <a class="btn btn-outline-secondary" href="{{ route('products.show', $product) }}">Cancel</a>
            <button class="btn btn-danger">Delete product or service</button>
        </form>
    </div></div>
</div>
@endsection
