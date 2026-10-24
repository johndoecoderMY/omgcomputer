@extends('layouts.guest')

@section('title', 'Shop grid preview | OMG Computer')

@section('content')
<section class="pt-8 pt-lg-9 pb-6">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <span class="badge rounded-pill badge-subtle-primary mb-2">Product grid preview</span>
                <h1 class="h2 text-1000 mb-2">Computer parts</h1>
                <p class="text-700 mb-0">Browse products currently available from OMG Computer.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('shop.preview.products.index', request()->query()) }}"><i class="fas fa-list me-2" aria-hidden="true"></i>List preview</a>
        </div>

        <form class="card border-0 shadow-sm mb-3 position-sticky" method="GET" action="{{ route('shop.preview.products.grid') }}" style="top: 5.5rem; z-index: 1020;">
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-md">
                        <label class="visually-hidden" for="preview-grid-search">Search products</label>
                        <input class="form-control" id="preview-grid-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search products">
                    </div>
                    <div class="col-md-auto">
                        <label class="visually-hidden" for="preview-grid-type">Filter by category</label>
                        <select class="form-select" id="preview-grid-type" name="type">
                            <option value="">All categories</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <label class="visually-hidden" for="preview-grid-sort">Sort products</label>
                        <select class="form-select" id="preview-grid-sort" name="sort" onchange="this.form.submit()">
                            <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                            <option value="price-low" @selected($filters['sort'] === 'price-low')>Price: low to high</option>
                            <option value="price-high" @selected($filters['sort'] === 'price-high')>Price: high to low</option>
                        </select>
                    </div>
                    <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit">Search</button></div>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="h6 mb-0">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ number_format($productCount) }} products</h2>
                <span class="small text-700">Availability and pricing may change.</span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row">
                    @forelse ($products as $product)
                        <div class="mb-4 col-md-6 col-lg-4">
                            <article class="border rounded-1 h-100 d-flex flex-column justify-content-between pb-3">
                                <div class="overflow-hidden">
                                    <a class="d-flex align-items-center justify-content-center rounded-top bg-body-tertiary text-primary text-decoration-none" href="{{ route('shop.preview.products.show', $product) }}" aria-label="View {{ $product->name }}" style="height: 12rem">
                                        <i class="fas fa-box-open fa-3x" aria-hidden="true"></i>
                                    </a>
                                    <div class="p-3">
                                        <p class="small text-700 mb-1">{{ $types[$product->type] ?? $product->type }}</p>
                                        <h2 class="h5"><a class="text-1000 text-decoration-none" href="{{ route('shop.preview.products.show', $product) }}">{{ $product->name }}</a></h2>
                                        <h3 class="h5 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</h3>
                                        <p class="small mb-1">Availability: <strong class="text-success">In stock</strong></p>
                                        <p class="small text-700 mb-0">{{ \Illuminate\Support\Str::limit($product->description ?: 'Contact us for product details.', 120) }}</p>
                                    </div>
                                </div>
                                <div class="px-3"><a class="btn btn-sm btn-outline-primary w-100" href="{{ route('shop.preview.products.show', $product) }}">View product details</a></div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12 text-center py-6">
                            <i class="fas fa-box-open text-500 fa-2x mb-3" aria-hidden="true"></i>
                            <h2 class="h5 text-1000">No products available</h2>
                            <p class="text-700 mb-0">Please check back later or contact us for availability.</p>
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
</section>
@endsection
