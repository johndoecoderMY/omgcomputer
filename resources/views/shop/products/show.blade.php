@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce / Products" :title="$product->name" :back-url="route('shop.products.index')" back-label="Back to products">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.products.edit', $product) }}"><i class="bi bi-pencil me-2"></i>Edit product</a>
        </x-slot>
    </x-page-header>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-center bg-body-tertiary text-primary" style="min-height: 18rem">
                    <i class="bi bi-box2 bi-5x" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-light border">{{ \App\Models\InventoryPart::TYPES[$product->type] ?? $product->type }}</span>
                        <span class="badge text-bg-{{ $product->is_for_sale ? 'success' : 'secondary' }}">{{ $product->is_for_sale ? 'Listed for sale' : 'Hidden from grid' }}</span>
                        <span class="badge text-bg-{{ $product->stock_quantity > 0 ? 'success' : 'warning' }}">{{ $product->stock_quantity }} in stock</span>
                    </div>
                    <h2 class="h3">{{ $product->name }}</h2>
                    <div class="text-primary fs-3 fw-bold mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</div>
                    <p class="text-pre-line">{{ $product->description ?: 'No product description provided.' }}</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">SKU</dt><dd class="col-sm-8">{{ $product->sku ?: '—' }}</dd>
                        <dt class="col-sm-4">Reorder level</dt><dd class="col-sm-8">{{ $product->reorder_level }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
