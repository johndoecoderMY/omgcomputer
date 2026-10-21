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
                @endif
                @if ($booking->customer && ! auth()->user()->hasRole('customer'))
                    <a class="btn btn-outline-primary" href="{{ route('customers.show', $booking->customer) }}">Open customer record</a>
                @endif
            </x-slot:actions>
        </x-page-header>

        <div class="card p-2">
            <div class="card-body">
                <dl class="row mb-0 gy-3">
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9"><x-status-badge :status="$booking->status" :label="\App\Models\Booking::STATUSES[$booking->status] ?? $booking->status" /></dd>
                    <dt class="col-sm-3">Customer</dt><dd class="col-sm-9">{{ $booking->customer_name }}</dd>
                    <dt class="col-sm-3">Email</dt><dd class="col-sm-9 text-break">{{ $booking->customer_email }}</dd>
                    <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $booking->customer_phone ?: '—' }}</dd>
                    <dt class="col-sm-3">Service</dt><dd class="col-sm-9">{{ $booking->service_name }}</dd>
                    <dt class="col-sm-3">Device</dt><dd class="col-sm-9">{{ $booking->device_model ?: '—' }}</dd>
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
