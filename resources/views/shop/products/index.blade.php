@extends('layouts.app')

@section('title', 'Shop products')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" title="Product list" description="Manage shop products and inventory from the staff catalogue.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.orders.status-board') }}"><i class="bi bi-truck me-2"></i>Order status</a>
            <a class="btn btn-outline-primary" href="{{ route('shop.products.grid') }}"><i class="bi bi-grid me-2"></i>Product grid</a>
            <a class="btn btn-primary" href="{{ route('shop.products.create') }}"><i class="bi bi-plus me-2"></i>Add product</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-body border-bottom">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-sm-auto">
                    <h2 class="h6 mb-0">{{ number_format($productCount) }} products in inventory</h2>
                    <p class="small text-body-secondary mb-0">Search and filter the full inventory catalogue.</p>
                </div>
                <div class="col-sm-auto d-flex flex-wrap align-items-center gap-2">
                    <label class="visually-hidden" for="shop-product-type-filter">Filter by category</label>
                    <select class="form-select form-select-sm" id="shop-product-type-filter" style="width: auto">
                        <option value="">All categories</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="visually-hidden" for="shop-product-sale-filter">Filter by storefront visibility</label>
                    <select class="form-select form-select-sm" id="shop-product-sale-filter" style="width: auto">
                        <option value="">All products</option>
                        <option value="yes">Listed for sale</option>
                        <option value="no">Hidden from shop</option>
                    </select>
                    <label class="visually-hidden" for="shop-products-search">Search products</label>
                    <input class="form-control form-control-sm" id="shop-products-search" type="search" placeholder="Search products…" style="max-width: 18rem" data-datatable-search="#shop-products-table">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="shop-products-table" data-datatable
                data-source="{{ route('shop.products.datatable') }}"
                data-datatable-filters="{{ json_encode([
                    ['name' => 'type', 'selector' => '#shop-product-type-filter'],
                    ['name' => 'sale', 'selector' => '#shop-product-sale-filter'],
                ]) }}"
                data-columns="{{ json_encode([
                    ['data' => 'product', 'name' => 'name'],
                    ['data' => 'category', 'name' => 'type'],
                    ['data' => 'stock_quantity', 'name' => 'stock_quantity'],
                    ['data' => 'selling_price', 'name' => 'selling_price'],
                    ['data' => 'sale_status', 'name' => 'is_for_sale'],
                    ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead>
                    <tr><th>Product</th><th>Category</th><th>Stock</th><th>Price</th><th>Storefront</th><th>Actions</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@include('shop.products._stock-modal', ['stockView' => 'list'])
@endsection
