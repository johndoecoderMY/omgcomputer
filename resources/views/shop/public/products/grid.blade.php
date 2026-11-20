@extends(auth()->check() && auth()->user()->hasRole('customer') ? 'layouts.app' : 'layouts.guest')

@section('title', __('Shop | OMG Computer'))
@section('meta_description', __('Browse computer parts, accessories, and ready-built PCs at the OMG Computer shop. Check product availability and contact us for details.'))

@section('content')
@php
    $isCustomerShop = auth()->check() && auth()->user()->hasRole('customer');
    $shopListingRoute = $isCustomerShop ? 'shop.panel' : 'shop.public';
    $shopProductRoute = $isCustomerShop ? 'shop.panel.products.show' : 'shop.public.products.show';
@endphp
<style>
    .shop-product-filters {
        position: sticky;
        top: 4.5rem;
        z-index: 1020;
        background-color: var(--falcon-card-bg, #fff);
        border: 1px solid rgba(23, 32, 51, .2) !important;
        box-shadow: 0 .75rem 1.5rem rgba(23, 32, 51, .16) !important;
    }

    .shop-product-filters-customer {
        top: 1rem;
    }

    .shop-filter-fab {
        display: none;
    }

    @media (max-width: 767.98px) {
        .shop-product-filters {
            display: none;
        }

        .shop-filter-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1030;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 .5rem 1.25rem rgba(23, 32, 51, .28);
        }

    }
</style>
<section class="pt-7 pt-lg-8 pb-6">
    <div class="container">
        <div class="rounded-4 bg-primary-subtle p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg">
                    <span class="badge rounded-pill bg-white text-primary mb-3"><i class="bi bi-shop me-1" aria-hidden="true"></i>{{ __('OMG Computer shop') }}</span>
                    <h1 class="display-6 fw-bold text-1000 mb-2">{{ __('Parts and PCs for your setup.') }}</h1>
                    <p class="lead text-700 mb-0">{{ __('Explore computer parts, accessories, and ready-built PCs.') }}@guest {{ __('Sign in to place an order.') }}@else @if(auth()->user()->hasRole('customer')) {{ __('Add products to your cart and check out online.') }}@else {{ __('Sign in with a customer account to place an order.') }}@endif @endguest</p>
                </div>
                <div class="col-lg-auto">
                    @guest
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="{{ route('login') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>{{ __('Sign in to shop') }}</a>
                    @elseif (auth()->user()->hasRole('customer'))
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="{{ route('shop.cart') }}"><i class="bi bi-cart3 me-2" aria-hidden="true"></i>{{ __('View cart') }}</a>
                    @endif
                </div>
            </div>
            @include('shop.public.products._custom-pc-quote')
        </div>

        <form class="shop-product-filters card border-0 shadow-sm mb-4 {{ $isCustomerShop ? 'shop-product-filters-customer' : '' }}" method="GET" action="{{ route($shopListingRoute) }}">
            @include('shop.public.products._filter-fields', ['idPrefix' => 'shop-grid', 'shopListingRoute' => $shopListingRoute])
        </form>

        <button class="shop-filter-fab btn btn-primary rounded-pill px-4 py-3" type="button" data-bs-toggle="modal" data-bs-target="#shop-filter-modal">
            <i class="bi bi-sliders" aria-hidden="true"></i>{{ __('Filters') }}
            @if ($filters['search'] || $filters['type'] || $filters['sort'] !== 'name')
                <span class="badge rounded-pill text-bg-light text-primary">{{ __('Active') }}</span>
            @endif
        </button>
        <div class="shop-filter-modal modal fade" id="shop-filter-modal" tabindex="-1" aria-labelledby="shop-filter-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="shop-filter-modal-title">{{ __('Search and filter products') }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <form method="GET" action="{{ route($shopListingRoute) }}">
                        @include('shop.public.products._filter-fields', ['idPrefix' => 'shop-modal', 'shopListingRoute' => $shopListingRoute])
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 text-1000 mb-0">{{ __('Products') }} <span class="text-body-secondary fw-normal">({{ number_format($productCount) }})</span></h2>
        </div>

        <div class="row g-3 g-lg-4">
            @forelse ($products as $product)
                <div class="col-6 col-lg-4 col-xl-3">
                    <article class="card border-0 shadow-sm h-100 overflow-hidden">
                        <a class="d-flex align-items-center justify-content-center bg-body-tertiary text-primary text-decoration-none" href="{{ route($shopProductRoute, $product) }}" aria-label="{{ __('View :name', ['name' => $product->name]) }}" style="aspect-ratio: 4 / 3">
                            @include('shop.public.products._stock-image', ['product' => $product, 'class' => 'w-100 h-100 object-fit-cover'])
                        </a>
                        <div class="card-body p-3 p-lg-4 d-flex flex-column">
                            <p class="small text-primary fw-semibold mb-1">{{ __($types[$product->type] ?? $product->type) }}</p>
                            <h3 class="h6 mb-2"><a class="text-1000 text-decoration-none" href="{{ route($shopProductRoute, $product) }}">{{ $product->name }}</a></h3>
                            <p class="small text-700 mb-3 d-none d-sm-block">{{ \Illuminate\Support\Str::limit($product->description ?: __('Contact us for product details.'), 90) }}</p>
                            <div class="mt-auto">
                                <p class="h5 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</p>
                                @auth
                                    @if (auth()->user()->hasRole('customer'))
                                        <form class="js-add-to-cart mb-2" action="{{ route('shop.cart.add', $product) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="quantity" value="1">
                                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-cart-plus me-1" aria-hidden="true"></i><span>{{ __('Add to cart') }}</span></button>
                                        </form>
                                    @endif
                                @endauth
                                <a class="btn btn-primary btn-sm w-100" href="{{ route($shopProductRoute, $product) }}">{{ __('View details') }}<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-center py-6 px-3">
                        <i class="bi bi-search text-500 bi-2x mb-3" aria-hidden="true"></i>
                        <h2 class="h5 text-1000">{{ __('No matching products') }}</h2>
                        <p class="text-700 mb-3">{{ __('Try another search or category, or check back later.') }}</p>
                        <a class="btn btn-primary rounded-pill align-self-center" href="{{ route($shopListingRoute) }}">{{ __('View all products') }}</a>
                    </div>
                </div>
            @endforelse
        </div>
        @if ($products->hasPages())
            <div class="mt-4">
                {{ $products->links('shop.partials.pagination') }}
            </div>
        @endif
        <p class="small text-body-secondary text-center mt-3 mb-0">{{ __('Availability and pricing may change.') }}</p>
    </div>
</section>
@auth
    @if (auth()->user()->hasRole('customer'))
        @include('shop.public.products._ajax-add-to-cart')
    @endif
@endauth
@endsection
