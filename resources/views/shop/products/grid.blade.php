@extends('layouts.app')

@section('title', 'Shop product grid')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" title="Product grid" description="Browse available inventory and prepare customer orders from the staff-only shop.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.products.index', request()->query()) }}"><i class="fas fa-list me-2"></i>Product list</a>
            <a class="btn btn-primary" href="{{ route('shop.cart') }}"><i class="fas fa-cart-shopping me-2"></i>View cart</a>
        </x-slot>
    </x-page-header>

    <form class="card mb-3" method="GET" action="{{ route('shop.products.grid') }}">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-lg">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <label class="visually-hidden" for="shop-grid-search">Search products</label>
                        <input class="form-control form-control-sm" id="shop-grid-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="Search products…" style="min-width: min(100%, 18rem)">
                        <label class="visually-hidden" for="shop-grid-type">Product category</label>
                        <select class="form-select form-select-sm" id="shop-grid-type" name="type" style="width: auto">
                            <option value="">All categories</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                        @if ($filters['search'] !== '' || $filters['type'] !== '')
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('shop.products.grid') }}">Clear</a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-auto">
                    <div class="d-flex align-items-center justify-content-lg-end gap-2">
                        <label class="small text-body-secondary text-nowrap" for="shop-grid-sort">Sort by</label>
                        <select class="form-select form-select-sm" id="shop-grid-sort" name="sort" onchange="this.form.submit()" aria-label="Sort products">
                            <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                            <option value="price-low" @selected($filters['sort'] === 'price-low')>Price: low to high</option>
                            <option value="price-high" @selected($filters['sort'] === 'price-high')>Price: high to low</option>
                        </select>
                        <a class="btn btn-sm btn-falcon-default" href="{{ route('shop.cart') }}" title="Shopping cart" aria-label="View shopping cart"><i class="fas fa-cart-shopping" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row flex-between-center g-2">
                <div class="col-sm-auto">
                    <h2 class="h6 mb-0">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ number_format($productCount) }} products</h2>
                </div>
                <div class="col-sm-auto small text-body-secondary">
                    In-stock products listed for sale
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                @forelse ($products as $product)
                    <div class="mb-4 col-md-6 col-lg-4">
                        <article class="border rounded-1 h-100 d-flex flex-column justify-content-between pb-3">
                            <div class="overflow-hidden">
                                <a class="d-flex align-items-center justify-content-center rounded-top bg-body-tertiary text-primary text-decoration-none" href="{{ route('shop.products.show', $product) }}" aria-label="View {{ $product->name }}" style="height: 12rem">
                                    <i class="fas fa-box-open fa-3x" aria-hidden="true"></i>
                                </a>
                                @if ($product->stock_quantity <= $product->reorder_level)
                                    <span class="badge rounded-pill bg-warning text-dark position-relative ms-3 mt-n4">Low stock</span>
                                @endif
                                <div class="p-3">
                                    <p class="small text-body-secondary mb-1">{{ $types[$product->type] ?? $product->type }}</p>
                                    <h2 class="h5"><a class="text-body text-decoration-none" href="{{ route('shop.products.show', $product) }}">{{ $product->name }}</a></h2>
                                    <p class="small text-body-secondary mb-3">{{ $product->sku ?: 'No SKU' }}</p>
                                    <h3 class="h5 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</h3>
                                    <p class="small mb-1">Stock: <strong class="text-{{ $product->stock_quantity > 0 ? 'success' : 'danger' }}">{{ $product->stock_quantity }} available</strong></p>
                                    <p class="small text-body-secondary mb-0">{{ \Illuminate\Support\Str::limit($product->description ?: 'Available for purchase.', 120) }}</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center px-3">
                                <a class="btn btn-sm btn-falcon-default" href="{{ route('shop.products.show', $product) }}">Details</a>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-falcon-default" type="button" data-bs-toggle="modal" data-bs-target="#shop-stock-modal"
                                        data-stock-action="{{ route('shop.products.stock', $product) }}"
                                        data-stock-name="{{ $product->name }}"
                                        data-stock-current="{{ $product->stock_quantity }}"
                                        title="Adjust quantity" aria-label="Adjust stock for {{ $product->name }}">
                                        <i class="fas fa-arrows-rotate" aria-hidden="true"></i>
                                    </button>
                                    <form method="POST" action="{{ route('shop.cart.add', $product) }}">
                                        @csrf
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="btn btn-sm btn-falcon-default" type="submit" title="Add to cart" aria-label="Add {{ $product->name }} to cart"><i class="fas fa-cart-plus" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-box-open text-body-secondary fa-2x mb-3" aria-hidden="true"></i>
                            <h2 class="h5">No products available</h2>
                            <p class="text-body-secondary mb-0">There are no in-stock products listed for sale yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
        @if ($products->hasPages())
            <div class="card-footer bg-body-tertiary d-flex justify-content-center py-3">
                {{ $products->links('shop.partials.pagination') }}
            </div>
        @endif
    </div>
</div>
@include('shop.products._stock-modal', ['stockView' => 'grid'])
@endsection
