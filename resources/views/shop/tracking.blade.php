@extends('layouts.app')

@section('title', 'Track a parcel')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop" title="Track a parcel"
        description="Enter a tracking number to view the latest courier status and scan history.">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('shop.my-orders.index') }}">
                <i class="bi bi-receipt me-2" aria-hidden="true"></i>My orders
            </a>
        </x-slot>
    </x-page-header>

    @unless ($apiKeyConfigured)
        <div class="alert alert-warning" role="status">
            Parcel tracking is temporarily unavailable. Please contact us for an update.
        </div>
    @endunless

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header fw-semibold">Tracking number</div>
                <form method="POST" action="{{ route('shop.tracking.lookup') }}">
                    @csrf
                    <div class="card-body">
                        <label class="form-label" for="tracking_number">Enter your tracking number</label>
                        <input class="form-control @error('tracking_number') is-invalid @enderror"
                            id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}"
                            maxlength="100" autocomplete="off" required>
                        @error('tracking_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" type="submit" @disabled(! $apiKeyConfigured)>
                            <i class="bi bi-search me-2" aria-hidden="true"></i>Track parcel
                        </button>
                    </div>
                </form>
            </div>
            <p class="small text-body-secondary mt-2">
                Tracking details are provided by the courier network through our tracking service. Your parcel number is sent securely from the server; API credentials are never exposed here.
            </p>
        </div>

        @if ($error = session('customer_tracking_error'))
            <div class="col-12">
                <div class="alert alert-danger" role="alert">{{ $error }}</div>
            </div>
        @endif

        @if ($tracking = session('customer_tracking_result'))
            <div class="col-12 col-xl-6">
                <x-shop.parcel-tracking-result :tracking="$tracking" />
            </div>
        @endif
    </div>
</div>
@endsection
