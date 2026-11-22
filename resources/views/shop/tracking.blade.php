@extends('layouts.app')

@section('title', 'Track a parcel')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop" title="Track a parcel"
        description="View the tracking number added to your order after it is dispatched.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.my-orders.index') }}">
                <i class="bi bi-receipt me-2" aria-hidden="true"></i>My orders
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            <p class="mb-3">When your order has been dispatched, we’ll add the tracking number to its details.</p>
            <p class="text-body-secondary mb-0">Use that number on the delivery service’s own tracking page or contact the service directly. We do not fetch live tracking updates here.</p>
        </div>
    </div>
</div>
@endsection
