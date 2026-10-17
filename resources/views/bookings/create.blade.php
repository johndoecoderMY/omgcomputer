@extends('bookings.layout')

@section('title', 'Book a repair appointment')

@section('content')
<div class="row g-4 align-items-start">
    <div class="col-lg-7">
        <p class="text-primary fw-bold text-uppercase small mb-1">Repair booking</p>
        <h1 class="h2 mb-2">Book an appointment</h1>
        <p class="text-body-secondary mb-4">Choose an open time for a repair consultation. The team will confirm your booking by email or phone.</p>
        @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="card mb-3">            <div class="card-header d-flex justify-content-between align-items-center"><div><h2 class="h5 mb-0">Choose a date</h2><div class="small text-body-secondary">Red dates contain booked appointment times.</div></div><span class="small text-body-secondary">MYT</span></div>
            <div class="card-body"><div id="booking-calendar" aria-label="Appointment availability calendar"></div></div>
        </div>
        <div class="card">            <div class="card-header"><h2 class="h5 mb-0">Available times for {{ $selectedDate->format('l, F j') }}</h2></div>
            <div class="card-body">
                @if ($slots)
                    <fieldset><legend class="visually-hidden">Select a start time</legend><div class="row row-cols-2 row-cols-sm-3 g-2">
                        @foreach ($slots as $slot)<div class="col"><input class="btn-check" id="slot-{{ str_replace(':', '', $slot) }}" type="radio" name="start_time" value="{{ $slot }}" form="booking-form" required @checked(old('start_time') === $slot)><label class="btn btn-outline-primary w-100" for="slot-{{ str_replace(':', '', $slot) }}">{{ $slot }}</label></div>@endforeach
                    </div></fieldset>
                @else
                    <p class="text-body-secondary mb-0">No appointments are available on this date. Choose another date on the calendar.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <form id="booking-form" class="card" method="POST" action="{{ route('bookings.store') }}">
            @csrf
            <div class="card-header"><h2 class="h5 mb-0">Your details</h2></div>
            <div class="card-body">
                <input type="hidden" name="booking_date" value="{{ $selectedDate->toDateString() }}">
                <div class="mb-3"><label class="form-label" for="service_id">Service</label><select class="form-select" id="service_id" name="service_id" required><option value="">Select service</option>@foreach ($services as $service)<option value="{{ $service['id'] }}" @selected(old('service_id') === $service['id'])>{{ $service['name'] }} · from RM{{ number_format($service['billing_price'], 2) }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label" for="customer_name">Name</label><input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required maxlength="255"></div>
                <div class="mb-3"><label class="form-label" for="customer_email">Email</label><input class="form-control" id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" autocomplete="email" required maxlength="255"></div>
                <div class="mb-3"><label class="form-label" for="customer_phone">Phone</label><input class="form-control" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" required maxlength="64"></div>
                <div class="mb-3"><label class="form-label" for="device_model">Device / model <span class="text-body-secondary">(optional)</span></label><input class="form-control" id="device_model" name="device_model" value="{{ old('device_model') }}" maxlength="255"></div>
                <div><label class="form-label" for="request_notes">What do you need help with?</label><textarea class="form-control" id="request_notes" name="request_notes" rows="4" maxlength="3000">{{ old('request_notes') }}</textarea></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary w-100" type="submit" @disabled(!$slots)>Request appointment</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('booking-calendar');
    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: 'dayGridMonth',
        initialDate: @json($selectedDate->toDateString()),
        timeZone: 'Asia/Kuala_Lumpur',
        height: 'auto',
        validRange: { start: @json(now('Asia/Kuala_Lumpur')->toDateString()) },
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: @json(route('bookings.events')),
        dateClick: (info) => {
            const target = new Date(`${info.dateStr}T00:00:00+08:00`);
            const today = new Date(new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Kuala_Lumpur' }).format(new Date()) + 'T00:00:00+08:00');
            if (target < today || target.getDay() === 0) return;
            window.location.href = `{{ route('bookings.create') }}?date=${encodeURIComponent(info.dateStr)}&month=${encodeURIComponent(info.dateStr.slice(0, 7))}`;
        },
    });
    calendar.render();
});
</script>
@endpush