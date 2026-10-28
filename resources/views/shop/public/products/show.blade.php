@extends(auth()->check() && auth()->user()->hasRole('customer') ? 'layouts.app' : 'layouts.guest')

@section('title', $product->name.' | OMG Computer')

@section('content')
<section class="pt-7 pt-lg-8 pb-6">
    <div class="container">
        <nav class="small mb-3" aria-label="Breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('shop.public') }}">Shop</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shop.public', ['type' => $product->type]) }}">{{ $types[$product->type] ?? $product->type }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="row g-0">
                <div class="col-lg-6 bg-body-tertiary p-3 p-lg-5">
                    <div class="d-flex align-items-center justify-content-center rounded-4 bg-white text-primary" style="min-height: clamp(16rem, 45vw, 30rem)">
                        <i class="bi {{ $product->type === 'ready_built_pc' ? 'bi-pc-display-horizontal' : 'bi-box2' }} bi-5x" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-body p-4 p-lg-5 h-100 d-flex flex-column">
                        <span class="badge rounded-pill badge-subtle-primary align-self-start mb-3">{{ $types[$product->type] ?? $product->type }}</span>
                        <h1 class="display-6 fw-bold text-1000 mb-3">{{ $product->name }}</h1>
                        <p class="h2 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</p>
                        <div class="d-flex align-items-center gap-2 text-success mb-4">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span class="fw-semibold">In stock</span>
                        </div>
                        <p class="text-700 mb-4">{{ $product->description ?: 'Contact us for more information about this product.' }}</p>

                        <div class="border-top pt-4 mt-auto">
                            <p class="small text-body-secondary mb-3">Availability and pricing may change.</p>
                            @guest
                                <a class="btn btn-primary btn-lg rounded-pill w-100" href="{{ route('shop.products.purchase', $product) }}"><i class="bi bi-lock me-2" aria-hidden="true"></i>Sign in to buy</a>
                            @elseif (auth()->user()->hasRole('customer'))
                                <form class="js-add-to-cart mb-2" action="{{ route('shop.cart.add', $product) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="btn btn-primary btn-lg rounded-pill w-100" type="submit"><i class="bi bi-cart-plus me-2" aria-hidden="true"></i><span>Add to cart</span></button>
                                </form>
                                <a class="btn btn-outline-primary rounded-pill w-100" href="{{ route('shop.cart') }}"><i class="bi bi-cart3 me-2" aria-hidden="true"></i>View cart</a>
                            @else
                                <p class="text-body-secondary">Sign in with a customer account to place an order.</p>
                            @endguest
                            <a class="btn btn-link text-decoration-none w-100 mt-2" href="{{ route('shop.public', ['type' => $product->type]) }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>More {{ strtolower($types[$product->type] ?? $product->type) }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('shop.public.products._custom-pc-quote')

        <div class="row g-4 mt-2">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm h-100" aria-labelledby="product-information-title">
                    <div class="card-body p-4">
                        <h2 class="h5 text-1000 mb-3" id="product-information-title">Product information</h2>
                        <p class="text-700 mb-0">{{ $product->description ?: 'Contact us for more information about this product.' }}</p>
                    </div>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="card border-0 shadow-sm h-100" aria-labelledby="product-specifications-title">
                    <div class="card-body p-4">
                        <h2 class="h5 text-1000 mb-3" id="product-specifications-title">Specifications</h2>
                        <dl class="mb-0">
                            <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                                <dt class="fw-normal text-body-secondary">Category</dt>
                                <dd class="mb-0 text-end">{{ $types[$product->type] ?? $product->type }}</dd>
                            </div>
                            <div class="d-flex justify-content-between gap-3 py-2">
                                <dt class="fw-normal text-body-secondary">Availability</dt>
                                <dd class="mb-0 text-success fw-semibold">In stock</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-5" aria-labelledby="related-products-title">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h4 text-1000 mb-0" id="related-products-title">You may also like</h2>
                    <a class="small" href="{{ route('shop.public', ['type' => $product->type]) }}">Browse {{ strtolower($types[$product->type] ?? $product->type) }}</a>
                </div>
                <div class="row g-3">
                    @foreach ($relatedProducts as $relatedProduct)
                        <div class="col-6 col-lg-3">
                            <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('shop.public.products.show', $relatedProduct) }}">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-body-tertiary text-primary mb-3" style="aspect-ratio: 4 / 3"><i class="bi bi-box2 bi-2x" aria-hidden="true"></i></div>
                                    <h3 class="h6 text-1000">{{ $relatedProduct->name }}</h3>
                                    <p class="text-primary fw-bold mb-0">RM {{ number_format((float) $relatedProduct->selling_price, 2) }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>
@auth
    @if (auth()->user()->hasRole('customer'))
        @include('shop.public.products._ajax-add-to-cart')
    @endif
@endauth
@endsection
