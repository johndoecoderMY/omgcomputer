@extends('layouts.app')

@section('title', 'My shop orders')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop" title="My orders" description="View your order status and follow shipment updates.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.tracking') }}">
                <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Track a parcel
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Order</th><th>Placed</th><th>Status</th><th>Payment</th><th class="text-end">Total</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('shop.my-orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->created_at->format('M j, Y') }}</td>
                            <td>{{ \App\Models\ShopOrder::STATUSES[$order->status] ?? $order->status }}</td>
                            <td>{{ ucfirst($order->payment_status) }}</td>
                            <td class="text-end text-nowrap">RM {{ number_format((float) $order->total, 2) }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('shop.my-orders.show', $order) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">You don’t have any shop orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
