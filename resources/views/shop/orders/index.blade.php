@extends('layouts.app')

@section('title', 'Shop orders')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" title="Shop orders" description="Review payments and manage customer order fulfillment.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.products.index') }}"><i class="bi bi-box me-2"></i>Products</a>
        </x-slot>
    </x-page-header>
    <div class="card p-2">
        <div class="d-flex justify-content-end mb-2">
            <label class="visually-hidden" for="shop-orders-search">Search orders</label>
            <input class="form-control" id="shop-orders-search" type="search" placeholder="Search orders…" style="max-width: 20rem" data-datatable-search="#shop-orders-table">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered" id="shop-orders-table" data-datatable
                data-source="{{ route('shop.orders.datatable') }}"
                data-columns="{{ json_encode([
                    ['data' => 'order', 'name' => 'order_number'],
                    ['data' => 'customer_name', 'name' => 'customer_name'],
                    ['data' => 'created_at', 'name' => 'created_at'],
                    ['data' => 'total', 'name' => 'total'],
                    ['data' => 'status_label', 'name' => 'status'],
                    ['data' => 'payment_label', 'name' => 'payment_status'],
                ]) }}">
                <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Total</th><th>Order status</th><th>Payment</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
