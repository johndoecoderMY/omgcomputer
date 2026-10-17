@extends('bookings.layout')

@section('title', 'Booking request received')

@section('content')
<section class="mx-auto" style="max-width:680px">
    <div class="card p-2">
        <div class="card-body p-4 p-lg-5">
            <div class="text-success mb-3"><span class="fas fa-circle-check fa-2x" aria-hidden="true"></span></div>
            <p class="text-primary fw-bold text-uppercase small mb-1">Request received</p>
            <h1 class="h2 mb-2">Your appointment is requested</h1>
            <p class="text-body-secondary">We’ll contact you to confirm the time. Keep this reference for your records.</p>
            <dl class="row mt-4 mb-0"><dt class="col-sm-4">Reference</dt><dd class="col-sm-8 fw-semibold">{{ $booking->reference }}</dd><dt class="col-sm-4">Service</dt><dd class="col-sm-8">{{ $booking->service_name }}</dd><dt class="col-sm-4">Date and time</dt><dd class="col-sm-8">{{ $booking->starts_at->setTimezone('Asia/Kuala_Lumpur')->format('l, F j, Y \a\t g:i A') }} MYT</dd><dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ \App\Models\Booking::STATUSES[$booking->status] }}</dd></dl>
            <a class="btn btn-outline-primary mt-4" href="{{ route('bookings.create') }}">Book another appointment</a>
        </div>
    </div>
</section>
@endsection