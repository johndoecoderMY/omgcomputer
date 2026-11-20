@extends('layouts.guest')

@section('title', __('Booking request received'))

@section('content')
<section class="bg-body-tertiary py-6 py-lg-7" style="padding-top: 9rem !important; min-height: 70vh;">
    <div class="container">
        <div class="card border-0 shadow-sm p-2 mx-auto" style="max-width: 720px;">
            <div class="card-body p-4 p-lg-5">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-4" style="width:64px;height:64px;"><span class="bi bi-check bi-2x" aria-hidden="true"></span></div>
                <p class="text-primary fw-bold text-uppercase fs-10 mb-2">{{ __('Request received') }}</p>
                <h1 class="display-6 fw-bold text-1000 mb-3">{{ __('Thanks, :name.', ['name' => $booking->customer_name]) }}</h1>
                <p class="lead text-700">{{ __('Your appointment request has been sent. Our team will contact you to confirm the time.') }}</p>
                <div class="bg-body-tertiary rounded-3 p-3 p-md-4 mt-4">
                    <dl class="row mb-0 gy-2">
                        <dt class="col-sm-4 text-700">{{ __('Reference') }}</dt><dd class="col-sm-8 fw-bold text-1000">{{ $booking->reference }}</dd>
                        <dt class="col-sm-4 text-700">{{ __('Service') }}</dt><dd class="col-sm-8 text-1000">{{ $booking->service_name }}</dd>
                        @if ($booking->customer_dropoff_at_shop)
                            <dt class="col-sm-4 text-700">{{ __('Drop-off and collection') }}</dt>
                            <dd class="col-sm-8 text-1000">{{ __('I will drop off and collect the device at the shop.') }}</dd>
                        @else
                            @if ($booking->pickup_address)
                                <dt class="col-sm-4 text-700">{{ __('Pickup address') }}</dt><dd class="col-sm-8 text-1000" style="white-space: pre-line">{{ $booking->pickup_address }}</dd>
                            @endif
                            @if ($booking->pickup_latitude !== null && $booking->pickup_longitude !== null)
                                <dt class="col-sm-4 text-700">{{ __('Pickup pin') }}</dt>
                                <dd class="col-sm-8 text-1000"><a href="https://www.openstreetmap.org/?mlat={{ $booking->pickup_latitude }}&mlon={{ $booking->pickup_longitude }}#map=16/{{ $booking->pickup_latitude }}/{{ $booking->pickup_longitude }}" target="_blank" rel="noopener noreferrer">{{ __('View pickup pin') }}</a></dd>
                            @endif
                            @if ($booking->dropoff_address || $booking->pickup_address || $booking->pickup_latitude !== null)
                                <dt class="col-sm-4 text-700">{{ __('Drop-off address') }}</dt><dd class="col-sm-8 text-1000" style="white-space: pre-line">{{ $booking->dropoff_address ?: __('Same as pickup address') }}</dd>
                            @endif
                            @if ($booking->dropoff_latitude !== null && $booking->dropoff_longitude !== null)
                                <dt class="col-sm-4 text-700">{{ __('Drop-off pin') }}</dt>
                                <dd class="col-sm-8 text-1000"><a href="https://www.openstreetmap.org/?mlat={{ $booking->dropoff_latitude }}&mlon={{ $booking->dropoff_longitude }}#map=16/{{ $booking->dropoff_latitude }}/{{ $booking->dropoff_longitude }}" target="_blank" rel="noopener noreferrer">{{ __('View drop-off pin') }}</a></dd>
                            @endif
                        @endif
                        <dt class="col-sm-4 text-700">{{ __('Date and time') }}</dt><dd class="col-sm-8 text-1000">{{ $booking->starts_at->setTimezone('Asia/Kuala_Lumpur')->format('l, F j, Y \a\t g:i A') }} MYT</dd>
                        <dt class="col-sm-4 text-700">{{ __('Status') }}</dt><dd class="col-sm-8 text-1000"><x-status-badge :status="$booking->status" :label="\App\Models\Booking::STATUSES[$booking->status] ?? $booking->status" /></dd>
                    </dl>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-primary rounded-pill px-4" href="{{ route('bookings.create') }}"><span class="bi bi-calendar-plus me-2"></span>{{ __('Book another appointment') }}</a>
                    <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ url('/') }}">{{ __('Back to home') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection