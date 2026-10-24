@extends('layouts.app')

@section('title', 'Customer order status preview')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <span class="small text-primary fw-bold text-uppercase">Customer-facing example</span>
            <h1 class="h2 mt-1 mb-2">Track your order</h1>
            <p class="text-body-secondary mb-0">This staff-only preview demonstrates the order status experience. The details below are sample data.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('shop.orders.status-board') }}"><i class="fas fa-arrow-left me-2"></i>Back to status board</a>
    </div>

    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-start">
                <div class="col-md">
                    <div class="small text-body-secondary">Order number</div>
                    <div class="h5 mb-0">SHOP-DEMO-ORDER-003</div>
                </div>
                <div class="col-md">
                    <div class="small text-body-secondary">Placed on</div>
                    <div class="fw-semibold">{{ now()->subDays(2)->format('M j, Y') }}</div>
                </div>
                <div class="col-md">
                    <div class="small text-body-secondary">Payment</div>
                    <span class="badge text-bg-success">Paid</span>
                </div>
                <div class="col-md">
                    <div class="small text-body-secondary">Order total</div>
                    <div class="fw-semibold">RM 399.00</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0">Order progress</h2>
        </div>
        <div class="card-body p-4">
            <div class="d-flex gap-3 pb-4">
                <span class="fas fa-circle-check text-success fs-5 mt-1" aria-hidden="true"></span>
                <div>
                    <h3 class="h6 mb-1">Order placed</h3>
                    <p class="small text-body-secondary mb-0">We received your order and payment.</p>
                </div>
            </div>
            <div class="d-flex gap-3 pb-4">
                <span class="fas fa-circle-check text-success fs-5 mt-1" aria-hidden="true"></span>
                <div>
                    <h3 class="h6 mb-1">Payment confirmed</h3>
                    <p class="small text-body-secondary mb-0">Your payment was confirmed securely.</p>
                </div>
            </div>
            <div class="d-flex gap-3 pb-4">
                <span class="fas fa-circle-dot text-primary fs-5 mt-1" aria-hidden="true"></span>
                <div>
                    <h3 class="h6 mb-1">Preparing your order</h3>
                    <p class="small text-body-secondary mb-0">Our team is preparing your items.</p>
                </div>
            </div>
            <div class="d-flex gap-3">
                <span class="fas fa-circle text-body-tertiary fs-5 mt-1" aria-hidden="true"></span>
                <div>
                    <h3 class="h6 mb-1">Ready for collection</h3>
                    <p class="small text-body-secondary mb-0">We will update this page when your order is ready.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
