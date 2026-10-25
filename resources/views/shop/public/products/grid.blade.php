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
            @include('shop.public.products._custom-pc-quote')
        </div>

        <form class="card border-0 shadow-sm mb-4" method="GET" action="{{ route('shop.public') }}">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md">
                        <label class="form-label small fw-semibold" for="shop-grid-search">Search products</label>
                        <input class="form-control" id="shop-grid-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search by product name or description">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold" for="shop-grid-type">Category</label>
                        <select class="form-select" id="shop-grid-type" name="type">
                            <option value="">All categories</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold" for="shop-grid-sort">Sort by</label>
                        <select class="form-select" id="shop-grid-sort" name="sort">
                            <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                            <option value="price-low" @selected($filters['sort'] === 'price-low')>Price: low to high</option>
                            <option value="price-high" @selected($filters['sort'] === 'price-high')>Price: high to low</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Search</button>
                        @if ($filters['search'] || $filters['type'] || $filters['sort'] !== 'name')
                            <a class="btn btn-outline-secondary" href="{{ route('shop.public') }}">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 text-1000 mb-0">Products <span class="text-body-secondary fw-normal">({{ number_format($productCount) }})</span></h2>
        </div>

        <div class="row g-3 g-lg-4">
            @forelse ($products as $product)
                <div class="col-6 col-lg-4 col-xl-3">
                    <article class="card border-0 shadow-sm h-100 overflow-hidden">
                        <a class="d-flex align-items-center justify-content-center bg-body-tertiary text-primary text-decoration-none" href="{{ route('shop.public.products.show', $product) }}" aria-label="View {{ $product->name }}" style="aspect-ratio: 4 / 3">
                            <i class="bi {{ $product->type === 'ready_built_pc' ? 'bi-pc-display-horizontal' : 'bi-box2' }} bi-3x" aria-hidden="true"></i>
                        </a>
                        <div class="card-body p-3 p-lg-4 d-flex flex-column">
                            <p class="small text-primary fw-semibold mb-1">{{ $types[$product->type] ?? $product->type }}</p>
                            <h3 class="h6 mb-2"><a class="text-1000 text-decoration-none" href="{{ route('shop.public.products.show', $product) }}">{{ $product->name }}</a></h3>
                            <p class="small text-700 mb-3 d-none d-sm-block">{{ \Illuminate\Support\Str::limit($product->description ?: 'Contact us for product details.', 90) }}</p>
                            <div class="mt-auto">
                                <p class="h5 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</p>
                                <a class="btn btn-outline-primary btn-sm w-100" href="{{ route('shop.public.products.show', $product) }}">View details<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-center py-6 px-3">
                        <i class="bi bi-search text-500 bi-2x mb-3" aria-hidden="true"></i>
                        <h2 class="h5 text-1000">No matching products</h2>
                        <p class="text-700 mb-3">Try another search or category, or check back later.</p>
                        <a class="btn btn-outline-primary rounded-pill align-self-center" href="{{ route('shop.public') }}">View all products</a>
                    </div>
                </div>
            @endforelse
        </div>
        @if ($products->hasPages())
            <div class="mt-4">
                {{ $products->links('shop.partials.pagination') }}
            </div>
        @endif
        <p class="small text-body-secondary text-center mt-3 mb-0">Availability and pricing may change.</p>
    </div>
</section>
@endsection
