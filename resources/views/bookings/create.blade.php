@extends($authenticatedBooking ? 'layouts.app' : 'layouts.guest')

@section('title', 'Book an appointment')

@section('content')
    @php
        $initialDate = old('booking_date', $selectedDate->toDateString());
        $bookingHelpWhatsappUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, I am not sure which service to book. Can you help me choose?');
    @endphp
    <style>
        .booking-help-section { background: #0b1727; }
        .booking-help-section .booking-help-kicker { color: #ff4b0b; }
        .booking-help-section .booking-help-copy { color: #a9bdd5; }
        .booking-help-section .booking-help-card { background: #111f30; }
    </style>
    @if ($authenticatedBooking)
        <div class="container-fluid py-3">
            <x-page-header eyebrow="Bookings" title="Book a service"
                description="Choose a date, select an available time, and tell us about your device."
                :back-url="route('bookings.index')" back-label="My bookings" />
            <div class="row justify-content-center">
                <div class="col-12">
    @else
                    <section class="pt-1 pb-8">
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

                                <form id="booking-form" method="POST"
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
                                                            date <span class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" type="date" id="booking-date"
                                                            name="booking_date" value="{{ $initialDate }}"
                                                            min="{{ now()->toDateString() }}" readonly required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="booking-time">Appointment
                                                            time <span class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" type="time" id="booking-time"
                                                            name="start_time" value="{{ old('start_time') }}" readonly
                                                            required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-name">Full name <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" id="customer-name" name="customer_name"
                                                            value="{{ old('customer_name', $authenticatedBooking ? auth()->user()->name : '') }}"
                                                            required autocomplete="name" @readonly($authenticatedBooking)>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-email">Email <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" id="customer-email" type="email"
                                                            name="customer_email"
                                                            value="{{ old('customer_email', $authenticatedBooking ? auth()->user()->email : '') }}"
                                                            required autocomplete="email" @readonly($authenticatedBooking)>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-phone">Phone <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" id="customer-phone" type="tel"
                                                            name="customer_phone"
                                                            value="{{ old('customer_phone', $authenticatedBooking ? auth()->user()->phone : '') }}"
                                                            required autocomplete="tel">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="service-id">Service <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
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
                                                    <div id="device-details"
                                                        @if (old('service_id') !== $deviceDetailsServiceId) hidden @endif>
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
                                                    </div>
                                                    <div class="d-flex justify-content-end">
                                                        <button class="btn btn-primary" type="submit">
                                                            Book a service
                                                        </button>
                                                    </div>
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
    <section class="booking-help-section py-5 mt-5" id="booking-help">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="booking-help-kicker fw-bold text-uppercase fs-10">Not sure what to pick?</span>
                    <h2 class="h2 fw-bold text-white mt-2 mb-3">We’ll help you find the right service.</h2>
                    <p class="booking-help-copy lead mb-0">Tell us what is happening and what device needs attention. We’ll help you decide what to book and figure out the next step.</p>
                </div>
                <div class="col-lg-6">
                    <div class="card booking-help-card border-0 shadow-lg">
                        <div class="card-body p-4">
                            <span class="bi bi-headset text-primary fs-2 mb-3" aria-hidden="true"></span>
                            <h3 class="h3 fw-bold text-white">Just contact us</h3>
                            <p class="booking-help-copy mb-3">Tell us what you need and we’ll help you figure out the next step.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary rounded-pill px-4" href="mailto:support@omgcomputer.my">
                                    <span class="bi bi-envelope me-2" aria-hidden="true"></span>Email us
                                </a>
                                <a class="btn btn-success rounded-pill px-4" href="{{ $bookingHelpWhatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                    <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Message us on WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
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
                    const bookingTime = document.getElementById("booking-time");
                    const serviceSelect = document.getElementById("service-id");
                    const deviceDetails = document.getElementById("device-details");
                    const deviceModel = document.getElementById("device-model");
                    const requestNotes = document.getElementById("request-notes");
                    const availabilityMessage = document.getElementById("availability-message");
                    const availableTimes = document.getElementById("available-times");
                    const timesModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("booking-times-modal"));
                    const availabilityUrl = @json(route('bookings.availability'));
                    const deviceDetailsServiceId = @json($deviceDetailsServiceId);
                    let availabilityRequest = 0;

                    function updateDeviceDetails() {
                        const showDeviceDetails = serviceSelect.value === deviceDetailsServiceId;
                        deviceDetails.hidden = !showDeviceDetails;
                        deviceModel.disabled = !showDeviceDetails;
                        requestNotes.disabled = !showDeviceDetails;
                    }

                    serviceSelect.addEventListener("change", updateDeviceDetails);
                    updateDeviceDetails();

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