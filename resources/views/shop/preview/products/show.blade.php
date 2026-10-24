@extends('layouts.guest')

@section('title', $product->name.' | Shop preview')

@section('content')
<section class="pt-8 pt-lg-9 pb-6">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <span class="badge rounded-pill badge-subtle-primary mb-2">Product details preview</span>
                <h1 class="h2 text-1000 mb-0">{{ $product->name }}</h1>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="{{ route('shop.preview.products.index') }}"><i class="fas fa-list me-2" aria-hidden="true"></i>List preview</a>
                <a class="btn btn-outline-primary" href="{{ route('shop.preview.products.grid') }}"><i class="fas fa-th-large me-2" aria-hidden="true"></i>Grid preview</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center justify-content-center rounded bg-body-tertiary text-primary" style="min-height: 22rem">
                            <i class="fas fa-box-open fa-5x" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <p class="small text-primary fw-bold text-uppercase mb-2">{{ $types[$product->type] ?? $product->type }}</p>
                        <h2 class="h3 text-1000">{{ $product->name }}</h2>
                        <div class="h3 text-primary mb-3">RM {{ number_format((float) $product->selling_price, 2) }}</div>
                        <p class="text-700">{{ $product->description ?: 'Contact us for more information about this product.' }}</p>
                        <p class="small mb-1">Availability: <strong class="text-success">In stock</strong></p>
                        <p class="small text-700 mb-4">Product availability and pricing may change.</p>
                        <a class="btn btn-primary" href="{{ route('shop.products.purchase', $product) }}"><i class="fas fa-lock me-2" aria-hidden="true"></i>Sign in to buy</a>
                    </div>
                </div>

                <div class="mt-5">
                    <ul class="nav nav-tabs" id="preview-product-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="preview-description-tab" data-bs-toggle="tab" data-bs-target="#preview-description" type="button" role="tab" aria-controls="preview-description" aria-selected="true">Description</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preview-specifications-tab" data-bs-toggle="tab" data-bs-target="#preview-specifications" type="button" role="tab" aria-controls="preview-specifications" aria-selected="false">Specifications</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="preview-product-tab-content">
                        <div class="tab-pane fade show active text-700" id="preview-description" role="tabpanel" aria-labelledby="preview-description-tab" tabindex="0">
                            <p class="mb-0">{{ $product->description ?: 'Contact us for more information about this product.' }}</p>
                        </div>
                        <div class="tab-pane fade" id="preview-specifications" role="tabpanel" aria-labelledby="preview-specifications-tab" tabindex="0">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Category</dt>
                                <dd class="col-sm-9">{{ $types[$product->type] ?? $product->type }}</dd>
                                <dt class="col-sm-3">Availability</dt>
                                <dd class="col-sm-9">In stock</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-5" aria-labelledby="related-products-title">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 text-1000 mb-0" id="related-products-title">Related products</h2>
                    <a class="small" href="{{ route('shop.preview.products.grid', ['type' => $product->type]) }}">Browse {{ strtolower($types[$product->type] ?? $product->type) }}</a>
                </div>
                <div class="row g-3">
                    @foreach ($relatedProducts as $relatedProduct)
                        <div class="col-sm-6 col-lg-3">
                            <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('shop.preview.products.show', $relatedProduct) }}">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-center rounded bg-body-tertiary text-primary mb-3" style="height: 8rem"><i class="fas fa-box-open fa-2x" aria-hidden="true"></i></div>
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
@endsection
