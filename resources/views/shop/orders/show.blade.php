@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce / Orders" :title="$order->order_number" :back-url="route('shop.orders.index')" back-label="Back to orders">
        <x-slot name="actions">
            @if ($order->checkout_url && $order->payment_status === 'pending')
                <a class="btn btn-primary" href="{{ $order->checkout_url }}" target="_blank" rel="noopener"><i class="bi bi-credit-card me-2"></i>Open CHiP checkout</a>
            @endif
        </x-slot>
    </x-page-header>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Order items</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Unit price</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr><td><div class="fw-semibold">{{ $item->name }}</div><div class="small text-body-secondary">{{ $item->sku ?: 'No SKU' }}</div></td><td class="text-end">RM {{ number_format((float) $item->unit_price, 2) }}</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">RM {{ number_format((float) $item->line_total, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end">RM {{ number_format((float) $order->total, 2) }}</th></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header fw-semibold">Order fulfillment</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-primary">{{ \App\Models\ShopOrder::STATUSES[$order->status] ?? $order->status }}</span>
                        <span class="badge text-bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($order->payment_status) }}</span>
                    </div>
                    @if ($order->payment_status === 'paid' && in_array($order->status, ['paid', 'processing'], true))
                        <form class="row g-2 align-items-end" method="POST" action="{{ route('shop.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <div class="col-sm-6">
                                <label class="form-label" for="status">Update status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="processing" @selected($order->status === 'processing')>Processing</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-sm-6"><button class="btn btn-outline-primary" type="submit">Save status</button></div>
                        </form>
                    @elseif ($order->payment_status !== 'paid')
                        <p class="text-body-secondary mb-0">Fulfillment actions become available after CHiP confirms payment.</p>
                    @endif
                    <hr>
                    <form method="POST" action="{{ route('shop.orders.tracking-number', $order) }}">
                        @csrf
                        @method('PATCH')
                        <label class="form-label" for="tracking_number">Shipment tracking number</label>
                        <div class="input-group">
                            <input class="form-control @error('tracking_number') is-invalid @enderror"
                                id="tracking_number" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}"
                                maxlength="100" placeholder="Enter after dispatch">
                            <button class="btn btn-outline-primary" type="submit">Save tracking</button>
                        </div>
                        @error('tracking_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">The customer can see this number and request live courier updates from their order page.</div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Customer</div>
                <div class="card-body">
                    <div class="fw-semibold">{{ $order->customer_name }}</div>
                    <div><a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a></div>
                    @if ($order->customer_phone)<div>{{ $order->customer_phone }}</div>@endif
                    @if ($order->customer_address)<hr><div class="small text-pre-line">{{ $order->customer_address }}</div>@endif
                </div>
            </div>
            <div class="card">
                <div class="card-header fw-semibold">Payment details</div>
                <div class="card-body small">
                    <div><span class="text-body-secondary">Placed:</span> {{ $order->created_at->format('M j, Y g:i A') }}</div>
                    @if ($order->paid_at)<div><span class="text-body-secondary">Paid:</span> {{ $order->paid_at->format('M j, Y g:i A') }}</div>@endif
                    <div><span class="text-body-secondary">Gateway:</span> CHiP Collect</div>
                    @if ($order->provider_purchase_id)<div class="text-break"><span class="text-body-secondary">Purchase:</span> {{ $order->provider_purchase_id }}</div>@endif
                    @if ($order->notes)<hr><div class="text-pre-line"><span class="text-body-secondary">Notes:</span> {{ $order->notes }}</div>@endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
