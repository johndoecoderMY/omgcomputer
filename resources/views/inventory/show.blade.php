@extends('layouts.app')

@section('title', $part->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Parts & inventory" :title="$part->name" :back-url="route('inventory.parts')" back-label="Back to parts">
        <x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('inventory.parts.edit', $part) }}">Edit</a><a class="btn btn-outline-danger" href="{{ route('inventory.parts.delete', $part) }}">Delete</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">SKU</dt><dd class="col-sm-9">{{ $part->sku ?: '—' }}</dd>
            <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ \App\Models\InventoryPart::TYPES[$part->type] ?? $part->type }}</dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $part->description ?: '—' }}</dd>
            <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">{{ $part->supplier?->name ?? '—' }}</dd>
            <dt class="col-sm-3">In stock</dt><dd class="col-sm-9">{{ $part->stock_quantity }} (reorder at {{ $part->reorder_level }})</dd>
            <dt class="col-sm-3">Unit cost</dt><dd class="col-sm-9">RM {{ number_format((float) $part->cost_price, 2) }}</dd>
            <dt class="col-sm-3">Sale price</dt><dd class="col-sm-9">RM {{ number_format((float) $part->selling_price, 2) }}</dd>
        </dl>
    </div></div>
</div>
@endsection
