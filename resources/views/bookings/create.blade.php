@extends($authenticatedBooking ? 'layouts.app' : 'layouts.guest')

@section('title', 'Book an appointment')

@section('content')
    @php
        $initialDate = old('booking_date', $selectedDate->toDateString());
    @endphp
    @if ($authenticatedBooking)
        <div class="container-fluid py-3">
            <x-page-header eyebrow="Bookings" title="Book a service"
                description="Choose a date, select an available time, and tell us about your device."
                :back-url="route('bookings.index')" back-label="My bookings" />
            <div class="row justify-content-center">
                <div class="col-12">
    @else
                    <section class="py-8">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h1 class="mb-2">Book an appointment</h1>
                                    <p class="mb-4">Choose a date, select an available time, and tell us about your device.</p>
                @endif

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

                                <form method="POST"
                                    action="{{ $authenticatedBooking ? route('bookings.account-store') : route('bookings.store') }}">
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
                                                        <label class="form-label" for="booking-date">Appointment
                                                            date</label>
                                                        <input class="form-control" type="date" id="booking-date"
                                                            name="booking_date" value="{{ $initialDate }}"
                                                            min="{{ now()->toDateString() }}" readonly required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="booking-time">Appointment
                                                            time</label>
                                                        <input class="form-control" type="time" id="booking-time"
                                                            name="start_time" value="{{ old('start_time') }}" readonly
                                                            required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-name">Full name</label>
                                                        <input class="form-control" id="customer-name" name="customer_name"
                                                            value="{{ old('customer_name', $authenticatedBooking ? auth()->user()->name : '') }}"
                                                            required autocomplete="name" @readonly($authenticatedBooking)>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-email">Email</label>
                                                        <input class="form-control" id="customer-email" type="email"
                                                            name="customer_email"
                                                            value="{{ old('customer_email', $authenticatedBooking ? auth()->user()->email : '') }}"
                                                            required autocomplete="email" @readonly($authenticatedBooking)>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-phone">Phone</label>
                                                        <input class="form-control" id="customer-phone" type="tel"
                                                            name="customer_phone"
                                                            value="{{ old('customer_phone', $authenticatedBooking ? auth()->user()->phone : '') }}"
                                                            required autocomplete="tel">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="service-id">Service</label>
                                                        <select class="form-select" id="service-id" name="service_id"
                                                            required>
                                                            <option value="">Choose a service</option>
                                                            @foreach ($services as $service)
                                                                <option value="{{ $service->id }}"
                                                                    @selected(old('service_id') == $service->id)>
                                                                    {{ $service->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="device-model">Device model</label>
                                                        <input class="form-control" id="device-model" name="device_model"
                                                            value="{{ old('device_model') }}" autocomplete="off">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="request-notes">What do you need help
                                                            with?</label>
                                                        <textarea class="form-control" id="request-notes"
                                                            name="request_notes"
                                                            rows="4">{{ old('request_notes') }}</textarea>
                                                    </div>
                                                    <button class="btn btn-primary" type="submit">
                                                        Book a service
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="modal fade" id="booking-times-modal" tabindex="-1"
                                    aria-labelledby="booking-times-title" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 class="modal-title h5" id="booking-times-title">Available times</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p id="availability-message" role="status" aria-live="polite">Loading
                                                    available times...</p>
                                                <div id="available-times"
                                                    class="d-flex flex-wrap justify-content-center gap-3"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($authenticatedBooking)
                            </div>
                        @else
                        </div>
                        </section>
                    @endif
@endsection

        @push('scripts')
            <script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const calendarEl = document.getElementById("calendar");
                    const bookingDate = document.getElementById("booking-date");
                    const bookingTime = document.getElementById("booking-time");
                    const availabilityMessage = document.getElementById("availability-message");
                    const availableTimes = document.getElementById("available-times");
                    const timesModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("booking-times-modal"));
                    const availabilityUrl = @json(route('bookings.availability'));
                    let availabilityRequest = 0;

                    function renderTimes(slots) {
                        availableTimes.replaceChildren();

                        slots.forEach(function (slot) {
                            const button = document.createElement("button");
                            button.type = "button";
                            button.className = "btn btn-outline-primary btn-lg";
                            button.textContent = new Date("1970-01-01T" + slot + ":00").toLocaleTimeString([], {
                                hour: "numeric",
                                minute: "2-digit"
                            });
                            button.addEventListener("click", function () {
                                bookingTime.value = slot;
                                timesModal.hide();
                            });
                            availableTimes.append(button);
                        });
                    }

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: "dayGridMonth",
                        dateClick: async function (info) {
                            const request = ++availabilityRequest;
                            calendar.select(info.dateStr);
                            bookingDate.value = info.dateStr;
                            bookingTime.value = "";
                            availableTimes.replaceChildren();
                            availabilityMessage.textContent = "Loading available times...";
                            timesModal.show();

                            try {
                                const response = await fetch(availabilityUrl + "?date=" + encodeURIComponent(info.dateStr), {
                                    headers: { "Accept": "application/json" }
                                });
                                if (!response.ok) {
                                    throw new Error("Availability could not be loaded.");
                                }

                                const data = await response.json();
                                if (request !== availabilityRequest) {
                                    return;
                                }

                                renderTimes(data.slots);
                                availabilityMessage.textContent = data.slots.length
                                    ? "Choose an available time."
                                    : "No times are available for this date.";
                            } catch (error) {
                                if (request === availabilityRequest) {
                                    availabilityMessage.textContent = "Unable to load available times. Please try again.";
                                }
                            }
                        }
                    });

                    calendar.render();
                });
            </script>
        @endpush