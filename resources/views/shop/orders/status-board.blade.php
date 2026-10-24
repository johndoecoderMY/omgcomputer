@extends('layouts.app')

@section('title', 'Order status board')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" title="Order status board" description="Track every order and move paid orders through fulfillment.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.orders.index') }}"><i class="fas fa-receipt me-2"></i>All orders</a>
            <a class="btn btn-outline-secondary" href="{{ route('shop.orders.customer-preview') }}"><i class="fas fa-eye me-2"></i>Customer status preview</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-body border-bottom">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-sm-auto">
                    <h2 class="h6 mb-0">Fulfillment queue</h2>
                    <p class="small text-body-secondary mb-0">Only paid orders can be moved to processing or completed.</p>
                </div>
                <div class="col-sm-auto d-flex flex-wrap gap-2">
                    <label class="visually-hidden" for="shop-order-status-filter">Filter by order status</label>
                    <select class="form-select form-select-sm" id="shop-order-status-filter" style="width: auto">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="visually-hidden" for="shop-status-search">Search orders</label>
                    <input class="form-control form-control-sm" id="shop-status-search" type="search" placeholder="Search orders…" style="max-width: 18rem" data-datatable-search="#shop-order-status-table">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="shop-order-status-table" data-datatable
                data-source="{{ route('shop.orders.status-board.datatable') }}"
                data-datatable-filters="{{ json_encode([['name' => 'status', 'selector' => '#shop-order-status-filter']]) }}"
                data-columns="{{ json_encode([
                    ['data' => 'order', 'name' => 'order_number'],
                    ['data' => 'customer', 'name' => 'customer_name'],
                    ['data' => 'total', 'name' => 'total'],
                    ['data' => 'payment_label', 'name' => 'payment_status'],
                    ['data' => 'status_label', 'name' => 'status'],
                    ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Update status</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
