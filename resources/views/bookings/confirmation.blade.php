@extends('layouts.guest')

@section('title', 'Booking request received')

@section('content')
<section class="bg-body-tertiary py-6 py-lg-7" style="padding-top: 9rem !important; min-height: 70vh;">
    <div class="container">
        <div class="card border-0 shadow-sm p-2 mx-auto" style="max-width: 720px;">
            <div class="card-body p-4 p-lg-5">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-4" style="width:64px;height:64px;"><span class="fas fa-check fa-2x" aria-hidden="true"></span></div>
                <p class="text-primary fw-bold text-uppercase fs-10 mb-2">Request received</p>
                <h1 class="display-6 fw-bold text-1000 mb-3">Thanks, {{ $booking->customer_name }}.</h1>
                <p class="lead text-700">Your appointment request has been sent. Our team will contact you to confirm the time.</p>
                <div class="bg-body-tertiary rounded-3 p-3 p-md-4 mt-4">
                    <dl class="row mb-0 gy-2">
                        <dt class="col-sm-4 text-700">Reference</dt><dd class="col-sm-8 fw-bold text-1000">{{ $booking->reference }}</dd>
                        <dt class="col-sm-4 text-700">Service</dt><dd class="col-sm-8 text-1000">{{ $booking->service_name }}</dd>
                        <dt class="col-sm-4 text-700">Date and time</dt><dd class="col-sm-8 text-1000">{{ $booking->starts_at->setTimezone('Asia/Kuala_Lumpur')->format('l, F j, Y \a\t g:i A') }} MYT</dd>
                        <dt class="col-sm-4 text-700">Status</dt><dd class="col-sm-8 text-1000"><x-status-badge :status="$booking->status" :label="\App\Models\Booking::STATUSES[$booking->status] ?? $booking->status" /></dd>
                    </dl>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-primary rounded-pill px-4" href="{{ route('bookings.create') }}"><span class="fas fa-calendar-plus me-2"></span>Book another appointment</a>
                    <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ url('/') }}">Back to home</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection