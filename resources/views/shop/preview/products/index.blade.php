@extends('layouts.guest')

@section('title', 'Shop preview | OMG Computer')

@section('content')
<section class="pt-8 pt-lg-9 pb-6">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <span class="badge rounded-pill badge-subtle-primary mb-2">Product list preview</span>
                <h1 class="h2 text-1000 mb-2">Computer parts</h1>
                <p class="text-700 mb-0">Browse products currently available from OMG Computer.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('shop.preview.products.grid', request()->query()) }}"><i class="fas fa-th-large me-2" aria-hidden="true"></i>Grid preview</a>
        </div>

        <form class="card border-0 shadow-sm mb-3 position-sticky" method="GET" action="{{ route('shop.preview.products.index') }}" style="top: 5.5rem; z-index: 1020;">
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-md">
                        <label class="visually-hidden" for="preview-product-search">Search products</label>
                        <input class="form-control" id="preview-product-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search products">
                    </div>
                    <div class="col-md-auto">
                        <label class="visually-hidden" for="preview-product-type">Filter by category</label>
                        <select class="form-select" id="preview-product-type" name="type">
                            <option value="">All categories</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <label class="visually-hidden" for="preview-product-sort">Sort products</label>
                        <select class="form-select" id="preview-product-sort" name="sort" onchange="this.form.submit()">
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

        <div class="card border-0 shadow-sm overflow-hidden">
            @forelse ($products as $product)
                <article class="p-3 p-lg-4 border-bottom {{ $loop->even ? 'bg-body-tertiary' : '' }}">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm-4 col-lg-3">
                            <a class="d-flex align-items-center justify-content-center rounded bg-body-tertiary text-primary text-decoration-none" href="{{ route('shop.preview.products.show', $product) }}" aria-label="View {{ $product->name }}" style="min-height: 10rem">
                                <i class="fas fa-box-open fa-3x" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="col-sm-8 col-lg-6">
                            <p class="small text-700 mb-1">{{ $types[$product->type] ?? $product->type }}</p>
                            <h2 class="h5 mb-2"><a class="text-1000 text-decoration-none" href="{{ route('shop.preview.products.show', $product) }}">{{ $product->name }}</a></h2>
                            <p class="text-700 mb-2">{{ \Illuminate\Support\Str::limit($product->description ?: 'Contact us for product details.', 180) }}</p>
                            <span class="badge badge-subtle-success">In stock</span>
                        </div>
                        <div class="col-lg-3 text-lg-end">
                            <div class="h4 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</div>
                            <a class="btn btn-outline-primary" href="{{ route('shop.preview.products.show', $product) }}">View product details</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="text-center py-6 px-3">
                    <i class="fas fa-box-open text-500 fa-2x mb-3" aria-hidden="true"></i>
                    <h2 class="h5 text-1000">No products available</h2>
                    <p class="text-700 mb-0">Please check back later or contact us for availability.</p>
                </div>
            @endforelse
            @if ($products->hasPages())
                <div class="card-footer bg-body-tertiary d-flex justify-content-center py-3">
                    {{ $products->links('shop.partials.pagination') }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
