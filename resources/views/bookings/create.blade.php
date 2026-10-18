@extends('layouts.guest')

@section('title', 'Book an appointment')

@section('content')
@php
    $initialDate = old('booking_date', $selectedDate->toDateString());
@endphp
<section class="py-8">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="mb-2">Book an appointment</h1>
                <p class="mb-4">Choose a date, select an available time, and tell us about your device.</p>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <p class="fw-bold mb-2">Please check the information below.</p>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('bookings.store') }}">
                    @csrf
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-body">
                                    <h2 class="h4">Choose a date</h2>
                                    <div id="calendar"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-body">
                                    <h2 class="h4">Your details</h2>
                                    <div class="mb-3">
                                        <label class="form-label" for="booking-date">Appointment date</label>
                                        <input class="form-control" type="date" id="booking-date" name="booking_date" value="{{ $initialDate }}" min="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="booking-time">Appointment time</label>
                                        <input class="form-control" type="time" id="booking-time" name="start_time" value="{{ old('start_time') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="customer-name">Full name</label>
                                        <input class="form-control" id="customer-name" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="customer-email">Email</label>
                                        <input class="form-control" id="customer-email" type="email" name="customer_email" value="{{ old('customer_email') }}" required autocomplete="email">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="customer-phone">Phone</label>
                                        <input class="form-control" id="customer-phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required autocomplete="tel">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="service-id">Service</label>
                                        <select class="form-select" id="service-id" name="service_id" required>
                                            <option value="">Choose a service</option>
                                            @foreach ($services as $service)
                                                <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="device-model">Device model</label>
                                        <input class="form-control" id="device-model" name="device_model" value="{{ old('device_model') }}" autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="request-notes">What do you need help with?</label>
                                        <textarea class="form-control" id="request-notes" name="request_notes" rows="4">{{ old('request_notes') }}</textarea>
                                    </div>
                                    <button class="btn btn-primary" type="submit">
                                        Book a service
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const calendarEl = document.getElementById("calendar");
        const bookingDate = document.getElementById("booking-date");
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "dayGridMonth",
            dateClick: function (info) {
                calendar.select(info.dateStr);
                bookingDate.value = info.dateStr;
            }
        });

        calendar.render();
    });
</script>
@endpush
