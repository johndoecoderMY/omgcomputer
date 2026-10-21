@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Catalogue / {{ $categoryLabel }}" :title="$product->name" :back-url="route('products.index')" back-label="Back to catalogue">
        <x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('products.edit', $product) }}">Edit</a><a class="btn btn-outline-danger" href="{{ route('products.delete', $product) }}">Delete</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $categoryLabel }}</dd>
            <dt class="col-sm-3">Availability</dt><dd class="col-sm-9"><span class="badge {{ $product->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $product->is_active ? 'Active — available for public booking' : 'Disabled — hidden from public booking' }}</span></dd>
            <dt class="col-sm-3">Scope</dt><dd class="col-sm-9">{{ $product->scope }}</dd>
            <dt class="col-sm-3">Target customer</dt><dd class="col-sm-9">{{ $product->audience }}</dd>
            <dt class="col-sm-3">Displayed price</dt><dd class="col-sm-9">{{ $product->price }}</dd>
            <dt class="col-sm-3">Billing starting price</dt><dd class="col-sm-9">RM {{ number_format((float) $product->billing_price, 2) }}</dd>
        </dl>
    </div></div>
</div>
@endsection
