@extends('layouts.app')

@section('title', 'Delete Available Time')

@section('content')
<div class="container py-3">
    <x-page-header eyebrow="Bookings" title="Delete available time" :back-url="route('booking-time-slots.show', $bookingTimeSlot)" back-label="Cancel" />
    <div class="card border-danger"><div class="card-body">
        <p>Remove <strong>{{ \App\Models\BookingTimeSlot::DAYS[$bookingTimeSlot->day_of_week] }} at {{ date('g:i A', strtotime($bookingTimeSlot->start_time)) }}</strong> from the recurring booking schedule?</p>
        <form method="POST" action="{{ route('booking-time-slots.destroy', $bookingTimeSlot) }}" class="d-flex justify-content-end gap-2">
            @csrf
            @method('DELETE')
            <a class="btn btn-outline-secondary" href="{{ route('booking-time-slots.show', $bookingTimeSlot) }}">Cancel</a>
            <button class="btn btn-danger">Delete available time</button>
        </form>
    </div></div>
</div>
@endsection
