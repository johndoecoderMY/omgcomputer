@extends('layouts.app')

@section('title', 'Booking '.$booking->reference)

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Bookings" :title="'Booking '.$booking->reference"
            :description="$booking->service_name"
            :back-url="route('bookings.calendar')" back-label="Bookings calendar">
            <x-slot:actions>
                @if ($booking->repairTicket)
                    <a class="btn btn-outline-primary" href="{{ route('tickets.show', $booking->repairTicket) }}">Open repair ticket</a>
                @elseif (! auth()->user()->hasRole('customer'))
                    <a class="btn btn-primary" href="{{ route('tickets.create', ['booking_id' => $booking->id]) }}">Create repair ticket</a>
                @endif
                @if ($booking->customer && ! auth()->user()->hasRole('customer'))
                    <a class="btn btn-outline-primary" href="{{ route('customers.show', $booking->customer) }}">Open customer record</a>
                @endif
            </x-slot:actions>
        </x-page-header>

        @unless (auth()->user()->hasRole('customer'))
            @if ($booking->customer?->warning)
                <div class="alert alert-warning" role="alert">
                    <div class="fw-semibold">Important customer warning</div>
                    <div class="text-pre-line mt-1">{{ $booking->customer->warning }}</div>
                </div>
            @endif
            @if ($booking->customer?->notes)
                <div class="alert alert-info" role="status">
                    <div class="fw-semibold">Internal customer notes</div>
                    <div class="text-pre-line mt-1">{{ $booking->customer->notes }}</div>
                </div>
            @endif
        @endunless

        <div class="card p-2">
            <div class="card-body">
                <dl class="row mb-0 gy-3">
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9"><x-status-badge :status="$booking->status" :label="\App\Models\Booking::STATUSES[$booking->status] ?? $booking->status" /></dd>
                    @if (! auth()->user()->hasRole('customer') && $booking->status === 'cancelled' && $booking->cancellation_reason)
                        <dt class="col-sm-3">Cancellation reason</dt>
                        <dd class="col-sm-9" style="white-space: pre-wrap">{{ $booking->cancellation_reason }}</dd>
                    @endif
                    <dt class="col-sm-3">Customer</dt><dd class="col-sm-9">{{ $booking->customer_name }}</dd>
                    <dt class="col-sm-3">Email</dt><dd class="col-sm-9 text-break">{{ $booking->customer_email }}</dd>
                    <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $booking->customer_phone ?: '—' }}</dd>
                    <dt class="col-sm-3">Service</dt><dd class="col-sm-9">{{ $booking->service_name }}</dd>
                    <dt class="col-sm-3">Device</dt><dd class="col-sm-9">{{ $booking->device_model ?: '—' }}</dd>
                    @if ($booking->customer_dropoff_at_shop)
                        <dt class="col-sm-3">{{ __('Drop-off and collection') }}</dt>
                        <dd class="col-sm-9">{{ __('I will drop off and collect the device at the shop.') }}</dd>
                    @else
                        <dt class="col-sm-3">Pickup address</dt>
                        <dd class="col-sm-9" style="white-space: pre-line">
                            {{ $booking->pickup_address ?: '—' }}
                            @if ($booking->pickup_latitude !== null && $booking->pickup_longitude !== null)
                                <a class="d-block" href="https://www.openstreetmap.org/?mlat={{ $booking->pickup_latitude }}&mlon={{ $booking->pickup_longitude }}#map=16/{{ $booking->pickup_latitude }}/{{ $booking->pickup_longitude }}" target="_blank" rel="noopener noreferrer">{{ __('View pickup pin') }}</a>
                            @endif
                        </dd>
                        <dt class="col-sm-3">Drop-off address</dt>
                        <dd class="col-sm-9" style="white-space: pre-line">
                            {{ $booking->dropoff_address ?: ($booking->pickup_address || $booking->pickup_latitude !== null ? 'Same as pickup address' : '—') }}
                            @if ($booking->dropoff_latitude !== null && $booking->dropoff_longitude !== null)
                                <a class="d-block" href="https://www.openstreetmap.org/?mlat={{ $booking->dropoff_latitude }}&mlon={{ $booking->dropoff_longitude }}#map=16/{{ $booking->dropoff_latitude }}/{{ $booking->dropoff_longitude }}" target="_blank" rel="noopener noreferrer">{{ __('View drop-off pin') }}</a>
                            @endif
                        </dd>
                    @endif
                    <dt class="col-sm-3">Appointment</dt>
                    <dd class="col-sm-9">
                        {{ $booking->starts_at->setTimezone('Asia/Kuala_Lumpur')->format('M j, Y g:i A') }}
                        – {{ $booking->starts_at->copy()->addMinutes($booking->duration_minutes)->setTimezone('Asia/Kuala_Lumpur')->format('g:i A') }} MYT
                    </dd>
                    <dt class="col-sm-3">Request notes</dt>
                    <dd class="col-sm-9" style="white-space: pre-wrap">{{ $booking->request_notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
