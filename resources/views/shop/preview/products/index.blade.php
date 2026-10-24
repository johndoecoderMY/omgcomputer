@extends('layouts.guest')

@section('title', 'Shop | OMG Computer')

@section('content')
<section class="pt-7 pt-lg-8 pb-6">
    <div class="container">
        <div class="rounded-4 bg-primary-subtle p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg">
                    <span class="badge rounded-pill bg-white text-primary mb-3"><i class="bi bi-shop me-1" aria-hidden="true"></i>OMG Computer shop</span>
                    <h1 class="display-6 fw-bold text-1000 mb-2">Parts and PCs for your setup.</h1>
                    <p class="lead text-700 mb-0">Explore computer parts, accessories, and ready-built PCs. Sign in to place an order.</p>
                </div>
                <div class="col-lg-auto">
                    <a class="btn btn-primary btn-lg rounded-pill px-4" href="{{ route('login') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>Sign in to shop</a>
                </div>
            </div>
        </div>
        @include('shop.preview.products._custom-pc-quote')

        <form class="card border-0 shadow-sm mb-4" method="GET" action="{{ route('shop.preview.products.index') }}">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md">
                        <label class="form-label small fw-semibold" for="preview-product-search">Search products</label>
                        <input class="form-control" id="preview-product-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search by product name or description">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold" for="preview-product-type">Category</label>
                        <select class="form-select" id="preview-product-type" name="type">
                            <option value="">All categories</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold" for="preview-product-sort">Sort by</label>
                        <select class="form-select" id="preview-product-sort" name="sort">
                            <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                            <option value="price-low" @selected($filters['sort'] === 'price-low')>Price: low to high</option>
                            <option value="price-high" @selected($filters['sort'] === 'price-high')>Price: high to low</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Search</button>
                        @if ($filters['search'] || $filters['type'] || $filters['sort'] !== 'name')
                            <a class="btn btn-outline-secondary" href="{{ route('shop.preview.products.index') }}">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 text-1000 mb-0">Products <span class="text-body-secondary fw-normal">({{ number_format($productCount) }})</span></h2>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="{{ route('shop.preview.products.grid', request()->query()) }}"><i class="bi bi-grid me-1" aria-hidden="true"></i>Grid view</a>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            @forelse ($products as $product)
                <article class="p-3 p-lg-4 border-bottom">
                    <div class="row g-3 g-lg-4 align-items-center">
                        <div class="col-4 col-sm-3 col-lg-2">
                            <a class="d-flex align-items-center justify-content-center rounded-3 bg-body-tertiary text-primary text-decoration-none" href="{{ route('shop.preview.products.show', $product) }}" aria-label="View {{ $product->name }}" style="aspect-ratio: 1">
                                <i class="bi {{ $product->type === 'ready_built_pc' ? 'bi-pc-display-horizontal' : 'bi-box2' }} bi-3x" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="col-8 col-sm-9 col-lg">
                            <p class="small text-primary fw-semibold mb-1">{{ $types[$product->type] ?? $product->type }}</p>
                            <h3 class="h5 mb-2"><a class="text-1000 text-decoration-none" href="{{ route('shop.preview.products.show', $product) }}">{{ $product->name }}</a></h3>
                            <p class="small text-700 mb-2 d-none d-sm-block">{{ \Illuminate\Support\Str::limit($product->description ?: 'Contact us for product details.', 150) }}</p>
                            <span class="badge rounded-pill badge-subtle-success"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>In stock</span>
                        </div>
                        <div class="col-12 col-lg-auto text-lg-end">
                            <div class="h4 text-primary mb-2">RM {{ number_format((float) $product->selling_price, 2) }}</div>
                            <a class="btn btn-outline-primary w-100" href="{{ route('shop.preview.products.show', $product) }}">View details<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="text-center py-6 px-3">
                    <i class="bi bi-search text-500 bi-2x mb-3" aria-hidden="true"></i>
                    <h2 class="h5 text-1000">No matching products</h2>
                    <p class="text-700 mb-3">Try another search or category, or check back later.</p>
                    <a class="btn btn-outline-primary rounded-pill" href="{{ route('shop.preview.products.index') }}">View all products</a>
                </div>
            @endforelse
            @if ($products->hasPages())
                <div class="card-footer bg-body py-3">
                    {{ $products->links('shop.partials.pagination') }}
                </div>
            @endif
        </div>
        <p class="small text-body-secondary text-center mt-3 mb-0">Availability and pricing may change.</p>
    </div>
</section>
@endsection
