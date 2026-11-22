@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop / My orders" :title="$order->order_number"
        :back-url="route('shop.my-orders.index')" back-label="Back to my orders" />

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Order items</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td><div class="fw-semibold">{{ $item->name }}</div><div class="small text-body-secondary">{{ $item->sku ?: 'No SKU' }}</div></td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end text-nowrap">RM {{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end text-nowrap">RM {{ number_format((float) $order->total, 2) }}</th></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Order status</div>
                <div class="card-body">
                    <p><span class="text-body-secondary">Order:</span> {{ \App\Models\ShopOrder::STATUSES[$order->status] ?? $order->status }}</p>
                    <p class="mb-0"><span class="text-body-secondary">Payment:</span> {{ ucfirst($order->payment_status) }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Shipment tracking</div>
                <div class="card-body">
                    @if ($order->tracking_number)
                        <p class="mb-2"><span class="text-body-secondary">Tracking number:</span> <span class="fw-semibold text-break">{{ $order->tracking_number }}</span></p>
                        <p class="text-body-secondary mb-0">Use this number on the delivery service’s tracking page or contact the service directly. Live tracking updates are not available here.</p>
                    @else
                        <p class="text-body-secondary mb-0">A tracking number will appear here when your order has shipped.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
