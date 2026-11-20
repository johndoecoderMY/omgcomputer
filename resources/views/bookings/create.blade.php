@extends($authenticatedBooking ? 'layouts.app' : 'layouts.guest')

@section('title', __('Book an appointment'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/leaflet/leaflet.css') }}">
    @vite('resources/css/booking-phone.css')
@endpush

@section('content')
    @php
        $bookingHelpWhatsappUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, I am not sure which service to book. Can you help me choose?');
        $bookingLocationHelpWhatsappUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, my pickup or drop-off location is outside the Kampar service area. Can you advise me?');
        $bookingLocationHelpEmailUrl = 'mailto:support@omgcomputer.my?subject=' . rawurlencode('Pickup or drop-off outside Kampar') . '&body=' . rawurlencode('My pickup or drop-off location is outside the Kampar service area. Can you advise me?');
    @endphp
    <style>
        .booking-help-section { background: #0b1727; }
        .booking-help-section .booking-help-kicker { color: #ff4b0b; }
        .booking-help-section .booking-help-copy { color: #a9bdd5; }
        .booking-help-section .booking-help-card { background: #111f30; }
        .booking-selection-card { display: flex; align-items: center; gap: .85rem; min-height: 4.5rem; padding: .85rem 1rem; border: 1px solid var(--bs-border-color); border-radius: .75rem; background: var(--bs-tertiary-bg); }
        .booking-selection-card.is-selected { border-color: rgba(var(--bs-primary-rgb), .35); background: rgba(var(--bs-primary-rgb), .06); }
        .booking-selection-icon { display: grid; width: 2.5rem; height: 2.5rem; flex: 0 0 2.5rem; place-items: center; border-radius: .65rem; background: var(--bs-body-bg); color: var(--bs-primary); font-size: 1.15rem; }
        .booking-selection-value { min-width: 0; }
        .booking-location-card .card-body { padding: .65rem; }
        .booking-location-card.is-active { border-color: var(--bs-primary); box-shadow: 0 0 0 .15rem rgba(var(--bs-primary-rgb), .12); }
        .booking-location-preview { overflow-wrap: anywhere; min-height: 1.5rem; }
        #booking-location-map { min-height: 320px; height: min(62vh, 620px); border-radius: .5rem; z-index: 0; }
        #calendar .booking-date-unavailable { cursor: not-allowed; }
        #calendar .booking-date-unavailable .fc-daygrid-day-frame { background: #f1f3f5; color: #858b92; }
        #calendar .booking-date-unavailable .fc-daygrid-day-number { color: #858b92; }
        #calendar .booking-date-available .fc-daygrid-day-frame { background: #dcfce7; }
        #calendar .booking-date-available .fc-daygrid-day-number { color: #166534; font-weight: 600; }
        #calendar .booking-date-selected .fc-daygrid-day-frame { background: rgba(255, 75, 11, .12); box-shadow: inset 0 0 0 2px #ff4b0b; border-radius: .5rem; }
        #calendar .booking-date-selected .fc-daygrid-day-number { color: #fff !important; background: #ff4b0b; font-weight: 700; }
        #calendar .fc-day-today .fc-daygrid-day-frame { box-shadow: inset 0 0 0 2px #ffb51b; border-radius: .5rem; }
        #calendar .fc-day-today.booking-date-available .fc-daygrid-day-frame { background: #dcfce7; }
        #calendar .fc-day-today.booking-date-unavailable .fc-daygrid-day-frame { background: #f1f3f5; }
        #calendar .fc-day-today .fc-daygrid-day-number { color: #fff !important; background: #ffb51b; font-weight: 700; border-radius: 50%; }
        #calendar .booking-date-selected.fc-day-today .fc-daygrid-day-number { color: #fff !important; background: #ffb51b !important; }
        #calendar .booking-date-selected.fc-day-today .fc-daygrid-day-frame { box-shadow: inset 0 0 0 2px #ffb51b, inset 0 0 0 4px #ff4b0b; }
        .booking-location-pin { background: transparent; border: 0; }
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
                                    <h1 class="mb-2">{{ __('Book an appointment') }}</h1>
                                    <p class="mb-4">{{ __('Choose a date, select an available time, and tell us about your device.') }}</p>
                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger" role="alert">
                                        <p class="fw-bold mb-2">{{ __('Please check the information below.') }}</p>
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
                                                    <h2 class="h4">{{ __('Choose a date') }}</h2>
                                                    <p class="form-text text-body-secondary" id="calendar-availability-status"
                                                        role="status" aria-live="polite">{{ __('Checking appointment availability...') }}</p>
                                                    <div id="calendar"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-5">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h2 class="h4">{{ __('Your details') }}</h2>
                                                    <div class="booking-selection-summary mb-4" aria-label="{{ __('Appointment date and time') }}">
                                                        <div class="booking-selection-card mb-2" id="booking-date-card">
                                                            <span class="booking-selection-icon bi bi-calendar-event" aria-hidden="true"></span>
                                                            <div class="booking-selection-value flex-grow-1">
                                                                <div class="small text-body-secondary">{{ __('Appointment date') }}</div>
                                                                <div class="fw-semibold" id="booking-date-summary" role="status" aria-live="polite">{{ __('Choose a date on the calendar') }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="booking-selection-card" id="booking-time-card">
                                                            <span class="booking-selection-icon bi bi-clock" aria-hidden="true"></span>
                                                            <div class="booking-selection-value flex-grow-1">
                                                                <div class="small text-body-secondary">{{ __('Appointment time') }} (MYT)</div>
                                                                <div class="fw-semibold" id="booking-time-summary" role="status" aria-live="polite">{{ __('Select a date first') }}</div>
                                                            </div>
                                                            <button class="btn btn-sm btn-outline-primary text-nowrap" id="booking-change-time" type="button" disabled>{{ __('Choose a time') }}</button>
                                                        </div>
                                                        <div class="alert alert-danger py-2 mt-2 mb-0" id="appointment-selection-error" role="alert" hidden>
                                                            {{ __('Select an appointment date and time.') }}
                                                        </div>
                                                        <input type="hidden" id="booking-date" name="booking_date" value="{{ old('booking_date') }}">
                                                        <input type="hidden" id="booking-time" name="start_time" value="{{ old('start_time') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-name">{{ __('Full name') }} <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" id="customer-name" name="customer_name"
                                                            value="{{ old('customer_name', $authenticatedBooking ? auth()->user()->name : '') }}"
                                                            required autocomplete="name" @readonly($authenticatedBooking)>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-email">{{ __('Email') }} <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" id="customer-email" type="email"
                                                            name="customer_email"
                                                            value="{{ old('customer_email', $authenticatedBooking ? auth()->user()->email : '') }}"
                                                            required autocomplete="email" @readonly($authenticatedBooking)>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="customer-phone">{{ __('Phone') }} <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <input class="form-control" id="customer-phone" type="tel"
                                                            name="customer_phone"
                                                            value="{{ old('customer_phone', $authenticatedBooking ? auth()->user()->phone : '') }}"
                                                            required autocomplete="tel" inputmode="tel">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="service-id">{{ __('Service') }} <span
                                                                class="text-danger" aria-hidden="true">*</span></label>
                                                        <select class="form-select" id="service-id" name="service_id"
                                                            required>
                                                            <option value="">{{ __('Choose a service') }}</option>
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
                                                            <label class="form-label" for="device-model">{{ __('Device model') }}</label>
                                                            <input class="form-control" id="device-model" name="device_model"
                                                                value="{{ old('device_model') }}" autocomplete="off">
                                                        </div>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-12">
                                                                <section class="card booking-location-card" id="pickup-location-card" aria-labelledby="pickup-location-title">
                                                                    <div class="card-body">
                                                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                                            <h3 class="h6 mb-0" id="pickup-location-title">{{ __('Pickup address') }}</h3>
                                                                            <span class="badge text-bg-secondary" id="pickup-pin-badge">{{ __('Pin not set') }}</span>
                                                                        </div>
                                                                        <label class="visually-hidden" for="pickup-address">{{ __('Pickup address') }}</label>
                                                                        <textarea class="form-control" id="pickup-address" name="pickup_address"
                                                                            rows="2" maxlength="1000" autocomplete="street-address">{{ old('pickup_address') }}</textarea>
                                                                        <div class="form-text">{{ __('Where should we collect the PC for repair?') }}</div>
                                                                        <div class="booking-location-preview small text-body-secondary mt-1" id="pickup-location-preview" aria-live="polite">{{ __('No address entered') }}</div>
                                                                        <div class="small text-body-secondary" id="pickup-pin-coordinates">{{ __('Pin not set') }}</div>
                                                                        <button class="btn btn-sm btn-outline-primary mt-2" type="button"
                                                                            data-booking-location-mode="pickup" aria-pressed="true">
                                                                            <span class="bi bi-geo-alt me-1" aria-hidden="true"></span>{{ __('Set pickup pin') }}
                                                                        </button>
                                                                    </div>
                                                                </section>
                                                            </div>
                                                            <div class="col-12">
                                                                <section class="card booking-location-card" id="dropoff-location-card" aria-labelledby="dropoff-location-title">
                                                                    <div class="card-body">
                                                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                                            <h3 class="h6 mb-0" id="dropoff-location-title">{{ __('Drop-off address') }}</h3>
                                                                            <span class="badge text-bg-secondary" id="dropoff-pin-badge">{{ __('Pin not set') }}</span>
                                                                        </div>
                                                                        <div class="form-check mb-1">
                                                                            <input class="form-check-input" id="dropoff-same-as-pickup"
                                                                                name="dropoff_same_as_pickup" type="checkbox" value="1"
                                                                                @checked(old('dropoff_same_as_pickup'))>
                                                                            <label class="form-check-label" for="dropoff-same-as-pickup">{{ __('Same as pickup address') }}</label>
                                                                        </div>
                                                                        <label class="visually-hidden" for="dropoff-address">{{ __('Drop-off address') }}</label>
                                                                        <textarea class="form-control" id="dropoff-address" name="dropoff_address"
                                                                            rows="2" maxlength="1000" autocomplete="street-address">{{ old('dropoff_address') }}</textarea>
                                                                        <div class="form-text">{{ __('Use the checkbox to copy the pickup address and pin, or set a separate drop-off pin.') }}</div>
                                                                        <div class="booking-location-preview small text-body-secondary mt-1" id="dropoff-location-preview" aria-live="polite">{{ __('No address entered') }}</div>
                                                                        <div class="small text-body-secondary" id="dropoff-pin-coordinates">{{ __('Pin not set') }}</div>
                                                                        <button class="btn btn-sm btn-outline-primary mt-2" id="dropoff-pin-button" type="button"
                                                                            data-booking-location-mode="dropoff" aria-pressed="false">
                                                                            <span class="bi bi-geo-alt me-1" aria-hidden="true"></span>{{ __('Set drop-off pin') }}
                                                                        </button>
                                                                    </div>
                                                                </section>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" id="pickup-latitude" name="pickup_latitude" value="{{ old('pickup_latitude') }}">
                                                        <input type="hidden" id="pickup-longitude" name="pickup_longitude" value="{{ old('pickup_longitude') }}">
                                                        <input type="hidden" id="dropoff-latitude" name="dropoff_latitude" value="{{ old('dropoff_latitude') }}">
                                                        <input type="hidden" id="dropoff-longitude" name="dropoff_longitude" value="{{ old('dropoff_longitude') }}">
                                                        <p class="form-text">{{ __('Pickup and drop-off are available within :radius km of Kampar.', ['radius' => $serviceAreaRadiusKm]) }}</p>
                                                        <p class="form-text">{{ __('Choose a pin button to open the map and set that location.') }}</p>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="request-notes">{{ __('What do you need help with?') }}</label>
                                                            <textarea class="form-control" id="request-notes"
                                                                name="request_notes"
                                                                rows="4">{{ old('request_notes') }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end">
                                                        <button class="btn btn-primary" type="submit">
                                                            {{ __('Book a service') }}
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
                                                <h2 class="modal-title h5" id="booking-times-title">{{ __('Available times') }}</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="{{ __('Close') }}"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p id="availability-message" role="status" aria-live="polite">{{ __('Loading available times...') }}</p>
                                                <div id="available-times"
                                                    class="d-flex flex-wrap justify-content-center gap-3"></div>
                                            </div>
                                        </div>
                                            </div>
                                        </div>
                                        <div class="modal fade" id="booking-location-modal" tabindex="-1"
                                            aria-labelledby="booking-location-modal-title" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-sm-down">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h2 class="modal-title h5" id="booking-location-modal-title">{{ __('Choose a location on the map') }}</h2>
                                                            <div class="small text-body-secondary">{{ __('Currently pinning') }} <strong id="booking-location-active-mode">{{ __('Pickup address') }}</strong></div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="{{ __('Close') }}"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="form-text">{{ __('Pickup and drop-off are available within :radius km of Kampar.', ['radius' => $serviceAreaRadiusKm]) }}</p>
                                                        <div class="d-flex flex-wrap justify-content-end gap-2 mb-2">
                                                            <button class="btn btn-sm btn-outline-primary" id="booking-find-address" type="button" disabled>
                                                                <span class="bi bi-search me-1" aria-hidden="true"></span>{{ __('Fill address from pin') }}
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-secondary" id="booking-use-location" type="button">
                                                                <span class="bi bi-crosshair me-1" aria-hidden="true"></span>{{ __('Use my current location') }}
                                                            </button>
                                                        </div>
                                                        <p class="form-text">{{ __('Address lookup is optional. When you select it, the pin coordinates are sent to OpenStreetMap to find a nearby address.') }}</p>
                                                        <div id="booking-location-map" role="application"
                                                            aria-label="{{ __('Map for selecting a pickup or drop-off location') }}"></div>
                                                        <p class="form-text mb-0 mt-2" id="booking-location-status" role="status" aria-live="polite">
                                                            {{ __('Tap the map or drag the pin to select the location.') }}
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-primary" type="button" data-bs-dismiss="modal">{{ __('Done') }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                <div class="modal fade" id="booking-out-of-area-modal" tabindex="-1"
                                    aria-labelledby="booking-out-of-area-title" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 class="modal-title h5" id="booking-out-of-area-title">{{ __('Outside our service area') }}</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="{{ __('Close') }}"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('That pin is outside our Kampar pickup and drop-off area.') }}</p>
                                                <p class="mb-0">{{ __('Contact us on WhatsApp or email and we will advise you.') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <a class="btn btn-outline-primary" href="{{ $bookingLocationHelpEmailUrl }}">
                                                    <span class="bi bi-envelope me-1" aria-hidden="true"></span>{{ __('Email us') }}
                                                </a>
                                                <a class="btn btn-success" href="{{ $bookingLocationHelpWhatsappUrl }}"
                                                    target="_blank" rel="noopener noreferrer">
                                                    <span class="bi bi-whatsapp me-1" aria-hidden="true"></span>{{ __('Message us on WhatsApp') }}
                                                </a>
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
                    <span class="booking-help-kicker fw-bold text-uppercase fs-10">{{ __('Not sure what to pick?') }}</span>
                    <h2 class="h2 fw-bold text-white mt-2 mb-3">{{ __('We’ll help you find the right service.') }}</h2>
                    <p class="booking-help-copy lead mb-0">{{ __('Tell us what is happening and what device needs attention. We’ll help you decide what to book and figure out the next step.') }}</p>
                </div>
                <div class="col-lg-6">
                    <div class="card booking-help-card border-0 shadow-lg">
                        <div class="card-body p-4">
                            <span class="bi bi-headset text-primary fs-2 mb-3" aria-hidden="true"></span>
                            <h3 class="h3 fw-bold text-white">{{ __('Just contact us') }}</h3>
                            <p class="booking-help-copy mb-3">{{ __('Tell us what you need and we’ll help you figure out the next step.') }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary rounded-pill px-4" href="mailto:support@omgcomputer.my">
                                    <span class="bi bi-envelope me-2" aria-hidden="true"></span>{{ __('Email us') }}
                                </a>
                                <a class="btn btn-success rounded-pill px-4" href="{{ $bookingHelpWhatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                    <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>{{ __('Message us on WhatsApp') }}
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
            @vite('resources/js/booking-phone.js')
            <script src="{{ asset('vendors/leaflet/leaflet.js') }}"></script>
            <script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const calendarEl = document.getElementById("calendar");
                    const bookingForm = document.getElementById("booking-form");
                    const bookingDate = document.getElementById("booking-date");
                    const bookingTime = document.getElementById("booking-time");
                    const bookingDateSummary = document.getElementById("booking-date-summary");
                    const bookingTimeSummary = document.getElementById("booking-time-summary");
                    const bookingDateCard = document.getElementById("booking-date-card");
                    const bookingTimeCard = document.getElementById("booking-time-card");
                    const changeTimeButton = document.getElementById("booking-change-time");
                    const appointmentSelectionError = document.getElementById("appointment-selection-error");
                    const serviceSelect = document.getElementById("service-id");
                    const deviceDetails = document.getElementById("device-details");
                    const deviceModel = document.getElementById("device-model");
                    const pickupAddress = document.getElementById("pickup-address");
                    const dropoffAddress = document.getElementById("dropoff-address");
                    const dropoffSameAsPickup = document.getElementById("dropoff-same-as-pickup");
                    const dropoffPinButton = document.getElementById("dropoff-pin-button");
                    const locationInputs = {
                        pickup: {
                            latitude: document.getElementById("pickup-latitude"),
                            longitude: document.getElementById("pickup-longitude"),
                        },
                        dropoff: {
                            latitude: document.getElementById("dropoff-latitude"),
                            longitude: document.getElementById("dropoff-longitude"),
                        },
                    };
                    const locationMapElement = document.getElementById("booking-location-map");
                    const locationPickerModalElement = document.getElementById("booking-location-modal");
                    const locationStatus = document.getElementById("booking-location-status");
                    const locationModeButtons = document.querySelectorAll("[data-booking-location-mode]");
                    const locationPresentation = {
                        pickup: {
                            card: document.getElementById("pickup-location-card"),
                            badge: document.getElementById("pickup-pin-badge"),
                            preview: document.getElementById("pickup-location-preview"),
                            coordinates: document.getElementById("pickup-pin-coordinates"),
                        },
                        dropoff: {
                            card: document.getElementById("dropoff-location-card"),
                            badge: document.getElementById("dropoff-pin-badge"),
                            preview: document.getElementById("dropoff-location-preview"),
                            coordinates: document.getElementById("dropoff-pin-coordinates"),
                        },
                    };
                    const activeLocationLabel = document.getElementById("booking-location-active-mode");
                    const useLocationButton = document.getElementById("booking-use-location");
                    const findAddressButton = document.getElementById("booking-find-address");
                    const requestNotes = document.getElementById("request-notes");
                    const availabilityMessage = document.getElementById("availability-message");
                    const availableTimes = document.getElementById("available-times");
                    const calendarAvailabilityStatus = document.getElementById("calendar-availability-status");
                    const timesModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("booking-times-modal"));
                    const locationPickerModal = bootstrap.Modal.getOrCreateInstance(locationPickerModalElement);
                    const locationHelpModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("booking-out-of-area-modal"));
                    const availabilityUrl = @json(route('bookings.availability'));
                    const availabilityEventsUrl = @json(route('bookings.events'));
                    const calendarLocale = @json(app()->getLocale() === 'zh_CN' ? 'zh-cn' : (app()->getLocale() === 'ms' ? 'ms' : 'en'));
                    const deviceDetailsServiceId = @json($deviceDetailsServiceId);
                    const locationMessages = {
                        pickupSet: @json(__('Pickup pin set'), JSON_UNESCAPED_UNICODE),
                        dropoffSet: @json(__('Drop-off pin set'), JSON_UNESCAPED_UNICODE),
                        locationDenied: @json(__('Your location could not be accessed. Allow location access or tap the map to set a pin.'), JSON_UNESCAPED_UNICODE),
                        locationUnsupported: @json(__('Your browser does not support location access.'), JSON_UNESCAPED_UNICODE),
                        mapUnavailable: @json(__('Map data could not be loaded. You can still type the address above.'), JSON_UNESCAPED_UNICODE),
                        addressLookupPending: @json(__('Finding the address for this pin...'), JSON_UNESCAPED_UNICODE),
                        addressLookupFailed: @json(__('Could not find an address for this pin. Please enter it manually.'), JSON_UNESCAPED_UNICODE),
                        addressLookupUnavailable: @json(__('Address lookup is temporarily unavailable. Please enter the address manually.'), JSON_UNESCAPED_UNICODE),
                        addressLookupRateLimited: @json(__('Please wait a moment before looking up another address.'), JSON_UNESCAPED_UNICODE),
                        addressLookupSuccess: @json(__('Address filled from the selected pin.'), JSON_UNESCAPED_UNICODE),
                        outsideArea: @json(__('That pin is outside our Kampar pickup and drop-off area.'), JSON_UNESCAPED_UNICODE),
                        addressNotEntered: @json(__('No address entered'), JSON_UNESCAPED_UNICODE),
                        sameAsPickup: @json(__('Same as pickup address'), JSON_UNESCAPED_UNICODE),
                        pinNotSet: @json(__('Pin not set'), JSON_UNESCAPED_UNICODE),
                        pinSet: @json(__('Pin set'), JSON_UNESCAPED_UNICODE),
                        pinCoordinates: @json(__('Pin coordinates'), JSON_UNESCAPED_UNICODE),
                        pickupAddress: @json(__('Pickup address'), JSON_UNESCAPED_UNICODE),
                        dropoffAddress: @json(__('Drop-off address'), JSON_UNESCAPED_UNICODE),
                    };
                    let availabilityRequest = 0;
                    let selectedAppointmentDate = bookingDate.value || null;
                    let locationMap = null;
                    let locationMarker = null;
                    let locationMode = "pickup";
                    let deviceDetailsEnabled = false;
                    const defaultMapCenter = @json($serviceAreaCenter);
                    const serviceAreaRadiusKm = @json($serviceAreaRadiusKm);
                    const locationPinIcon = window.L?.divIcon({
                        className: "booking-location-pin",
                        html: '<svg viewBox="0 0 28 40" width="28" height="40" aria-hidden="true"><path d="M14 1C6.82 1 1 6.82 1 14c0 9.5 13 25 13 25s13-15.5 13-25C27 6.82 21.18 1 14 1Z" fill="#ff4b0b" stroke="#fff" stroke-width="2"/><circle cx="14" cy="14" r="4.5" fill="#fff"/></svg>',
                        iconSize: [28, 40],
                        iconAnchor: [14, 39],
                    });

                    function getLocationCoordinates(mode) {
                        if (mode === "dropoff" && dropoffSameAsPickup.checked) {
                            return getLocationCoordinates("pickup");
                        }

                        const latitude = Number.parseFloat(locationInputs[mode].latitude.value);
                        const longitude = Number.parseFloat(locationInputs[mode].longitude.value);
                        return Number.isFinite(latitude) && Number.isFinite(longitude)
                            ? [latitude, longitude]
                            : null;
                    }

                    function isWithinKamparServiceArea(coordinates) {
                        const [latitude, longitude] = coordinates;
                        const toRadians = (degrees) => degrees * Math.PI / 180;
                        const latitudeDelta = toRadians(latitude - defaultMapCenter[0]);
                        const longitudeDelta = toRadians(longitude - defaultMapCenter[1]);
                        const startLatitude = toRadians(defaultMapCenter[0]);
                        const endLatitude = toRadians(latitude);
                        const distanceFactor = Math.sin(latitudeDelta / 2) ** 2
                            + Math.cos(startLatitude) * Math.cos(endLatitude) * Math.sin(longitudeDelta / 2) ** 2;
                        const boundedDistanceFactor = Math.min(1, Math.max(0, distanceFactor));
                        const distanceKm = 6371 * 2 * Math.atan2(Math.sqrt(boundedDistanceFactor), Math.sqrt(1 - boundedDistanceFactor));

                        return distanceKm <= serviceAreaRadiusKm;
                    }

                    function showOutOfAreaModal() {
                        locationStatus.textContent = locationMessages.outsideArea;
                        const showContactModal = function () {
                            locationHelpModal.show();
                        };
                        if (locationPickerModalElement.classList.contains("show")) {
                            locationPickerModalElement.addEventListener("hidden.bs.modal", showContactModal, { once: true });
                            locationPickerModal.hide();
                        } else {
                            showContactModal();
                        }
                    }

                    function updateLocationSummaries() {
                        Object.keys(locationPresentation).forEach(function (mode) {
                            const presentation = locationPresentation[mode];
                            const address = mode === "pickup" || dropoffSameAsPickup.checked
                                ? pickupAddress.value.trim()
                                : dropoffAddress.value.trim();
                            const coordinates = getLocationCoordinates(mode);
                            presentation.card.classList.toggle("is-active", mode === locationMode);
                            presentation.preview.textContent = address
                                || (mode === "dropoff" && (dropoffSameAsPickup.checked || pickupAddress.value.trim())
                                    ? locationMessages.sameAsPickup
                                    : locationMessages.addressNotEntered);
                            presentation.badge.textContent = coordinates ? locationMessages.pinSet : locationMessages.pinNotSet;
                            presentation.badge.classList.toggle("text-bg-success", Boolean(coordinates));
                            presentation.badge.classList.toggle("text-bg-secondary", !coordinates);
                            presentation.coordinates.textContent = coordinates
                                ? `${locationMessages.pinCoordinates}: ${coordinates[0].toFixed(5)}, ${coordinates[1].toFixed(5)}`
                                : locationMessages.pinNotSet;
                        });
                        activeLocationLabel.textContent = locationMode === "pickup"
                            ? locationMessages.pickupAddress
                            : locationMessages.dropoffAddress;
                    }

                    function updateDropoffSameAsPickup() {
                        const sameAsPickup = dropoffSameAsPickup.checked;
                        dropoffAddress.disabled = !deviceDetailsEnabled || sameAsPickup;
                        dropoffPinButton.disabled = !deviceDetailsEnabled || sameAsPickup;
                        if (sameAsPickup && locationMode === "dropoff") {
                            locationMode = "pickup";
                        }
                        updateLocationSummaries();
                        if (locationMap) {
                            showLocationForMode();
                        }
                    }

                    function saveLocationPin(coordinates) {
                        if (!isWithinKamparServiceArea(coordinates)) {
                            const previousCoordinates = getLocationCoordinates(locationMode);
                            if (locationMarker && previousCoordinates) {
                                locationMarker.setLatLng(previousCoordinates);
                            } else if (locationMarker) {
                                locationMap.removeLayer(locationMarker);
                                locationMarker = null;
                            }
                            showOutOfAreaModal();
                            return;
                        }

                        const [latitude, longitude] = coordinates;
                        if (!locationMarker) {
                            locationMarker = window.L.marker(coordinates, { draggable: true, icon: locationPinIcon }).addTo(locationMap);
                            locationMarker.on("dragend", function () {
                                const position = locationMarker.getLatLng();
                                saveLocationPin([position.lat, position.lng]);
                            });
                        } else {
                            locationMarker.setLatLng(coordinates);
                        }

                        locationInputs[locationMode].latitude.value = latitude.toFixed(7);
                        locationInputs[locationMode].longitude.value = longitude.toFixed(7);
                        findAddressButton.disabled = false;
                        locationStatus.textContent = `${locationMode === "pickup" ? locationMessages.pickupSet : locationMessages.dropoffSet}: ${latitude.toFixed(5)}, ${longitude.toFixed(5)}.`;
                        updateLocationSummaries();
                    }

                    function showLocationForMode() {
                        updateLocationSummaries();
                        locationModeButtons.forEach(function (button) {
                            const isSelected = button.dataset.bookingLocationMode === locationMode;
                            button.classList.toggle("active", isSelected);
                            button.setAttribute("aria-pressed", isSelected ? "true" : "false");
                        });

                        if (!locationMap) {
                            return;
                        }

                        const coordinates = getLocationCoordinates(locationMode);
                        findAddressButton.disabled = !coordinates;
                        if (coordinates) {
                            if (!isWithinKamparServiceArea(coordinates)) {
                                locationInputs[locationMode].latitude.value = "";
                                locationInputs[locationMode].longitude.value = "";
                                findAddressButton.disabled = true;
                                if (locationMarker) {
                                    locationMap.removeLayer(locationMarker);
                                    locationMarker = null;
                                }
                                locationMap.setView(defaultMapCenter, 12);
                                showOutOfAreaModal();
                                return;
                            }

                            if (!locationMarker) {
                                locationMarker = window.L.marker(coordinates, { draggable: true, icon: locationPinIcon }).addTo(locationMap);
                                locationMarker.on("dragend", function () {
                                    const position = locationMarker.getLatLng();
                                    saveLocationPin([position.lat, position.lng]);
                                });
                            } else {
                                locationMarker.setLatLng(coordinates);
                            }
                            locationMap.setView(coordinates, Math.max(locationMap.getZoom(), 15));
                            return;
                        }

                        if (locationMarker) {
                            locationMap.removeLayer(locationMarker);
                            locationMarker = null;
                        }
                        locationMap.setView(defaultMapCenter, 12);
                    }

                    function initializeLocationMap() {
                        if (locationMap) {
                            window.setTimeout(function () { locationMap.invalidateSize(); }, 100);
                            return;
                        }
                        if (!window.L) {
                            locationStatus.textContent = locationMessages.mapUnavailable;
                            return;
                        }

                        try {
                            const latitudeBounds = serviceAreaRadiusKm / 111.045;
                            const longitudeBounds = serviceAreaRadiusKm / (111.045 * Math.cos(defaultMapCenter[0] * Math.PI / 180));
                            const serviceAreaBounds = window.L.latLngBounds(
                                [defaultMapCenter[0] - latitudeBounds, defaultMapCenter[1] - longitudeBounds],
                                [defaultMapCenter[0] + latitudeBounds, defaultMapCenter[1] + longitudeBounds],
                            );
                            locationMap = window.L.map(locationMapElement, {
                                maxBounds: serviceAreaBounds,
                                maxBoundsViscosity: 1,
                            }).setView(defaultMapCenter, 12);
                            const tileLayer = window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                                maxZoom: 19,
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
                            }).addTo(locationMap);
                            window.L.circle(defaultMapCenter, {
                                radius: serviceAreaRadiusKm * 1000,
                                color: "#0d6efd",
                                fillColor: "#0d6efd",
                                fillOpacity: 0.08,
                            }).addTo(locationMap);
                            tileLayer.on("tileerror", function () {
                                locationStatus.textContent = locationMessages.mapUnavailable;
                            });
                            locationMap.on("click", function (event) {
                                saveLocationPin([event.latlng.lat, event.latlng.lng]);
                            });
                            showLocationForMode();
                            window.setTimeout(function () { locationMap.invalidateSize(); }, 100);
                        } catch (error) {
                            locationMap = null;
                            console.error("Could not initialize the booking location map.", error);
                            locationStatus.textContent = locationMessages.mapUnavailable;
                        }
                    }

                    locationModeButtons.forEach(function (button) {
                        button.addEventListener("click", function () {
                            locationMode = button.dataset.bookingLocationMode;
                            showLocationForMode();
                            locationPickerModal.show();
                        });
                    });
                    locationPickerModalElement.addEventListener("shown.bs.modal", function () {
                        initializeLocationMap();
                    });
                    pickupAddress.addEventListener("input", updateLocationSummaries);
                    dropoffAddress.addEventListener("input", updateLocationSummaries);
                    dropoffSameAsPickup.addEventListener("change", updateDropoffSameAsPickup);
                    useLocationButton.addEventListener("click", function () {
                        if (!navigator.geolocation) {
                            locationStatus.textContent = locationMessages.locationUnsupported;
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(function (position) {
                            if (!locationMap) {
                                initializeLocationMap();
                            }
                            if (!locationMap) {
                                locationStatus.textContent = locationMessages.mapUnavailable;
                                return;
                            }

                            const coordinates = [position.coords.latitude, position.coords.longitude];
                            locationMap.setView(coordinates, 16);
                            saveLocationPin(coordinates);
                        }, function () {
                            locationStatus.textContent = locationMessages.locationDenied;
                        }, { enableHighAccuracy: true, timeout: 10000 });
                    });

                    findAddressButton.addEventListener("click", async function () {
                        const mode = locationMode;
                        const coordinates = getLocationCoordinates(mode);
                        if (!coordinates) {
                            locationStatus.textContent = locationMessages.addressLookupFailed;
                            return;
                        }

                        findAddressButton.disabled = true;
                        locationStatus.textContent = locationMessages.addressLookupPending;
                        try {
                            const response = await fetch(@json(route('bookings.reverse-geocode')), {
                                method: "POST",
                                headers: {
                                    "Accept": "application/json",
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": bookingForm.querySelector('input[name="_token"]').value,
                                },
                                body: JSON.stringify({ latitude: coordinates[0], longitude: coordinates[1] }),
                            });
                            const result = await response.json();
                            if (!response.ok) {
                                locationStatus.textContent = response.status === 429
                                    ? locationMessages.addressLookupRateLimited
                                    : response.status === 503
                                        ? locationMessages.addressLookupUnavailable
                                        : locationMessages.addressLookupFailed;
                                return;
                            }

                            (mode === "pickup" ? pickupAddress : dropoffAddress).value = result.address;
                            locationStatus.textContent = locationMessages.addressLookupSuccess;
                            updateLocationSummaries();
                        } catch (error) {
                            console.error("Could not look up the address for a booking pin.", error);
                            locationStatus.textContent = locationMessages.addressLookupUnavailable;
                        } finally {
                            findAddressButton.disabled = !getLocationCoordinates(locationMode);
                        }
                    });

                    function updateDeviceDetails() {
                        const showDeviceDetails = serviceSelect.value === deviceDetailsServiceId;
                        deviceDetailsEnabled = showDeviceDetails;
                        deviceDetails.hidden = !showDeviceDetails;
                        deviceModel.disabled = !showDeviceDetails;
                        pickupAddress.disabled = !showDeviceDetails;
                        dropoffSameAsPickup.disabled = !showDeviceDetails;
                        dropoffAddress.disabled = !showDeviceDetails || dropoffSameAsPickup.checked;
                        dropoffPinButton.disabled = !showDeviceDetails || dropoffSameAsPickup.checked;
                        Object.values(locationInputs).forEach(function (inputs) {
                            inputs.latitude.disabled = !showDeviceDetails;
                            inputs.longitude.disabled = !showDeviceDetails;
                        });
                        requestNotes.disabled = !showDeviceDetails;
                        updateLocationSummaries();
                    }

                    serviceSelect.addEventListener("change", updateDeviceDetails);
                    updateDeviceDetails();

                    function renderTimes(slots) {
                        availableTimes.replaceChildren();

                        slots.forEach(function (slot) {
                            const button = document.createElement("button");
                            button.type = "button";
                            button.className = "btn btn-outline-primary btn-lg";
                            button.textContent = formatAppointmentTime(slot);
                            button.setAttribute("aria-pressed", String(slot === bookingTime.value));
                            if (slot === bookingTime.value) {
                                button.classList.add("active");
                            }
                            button.addEventListener("click", function () {
                                bookingTime.value = slot;
                                appointmentSelectionError.hidden = true;
                                updateAppointmentSummary();
                                timesModal.hide();
                            });
                            availableTimes.append(button);
                        });
                    }

                    const availabilityMessages = {
                        loading: @json(__('Loading available times...'), JSON_UNESCAPED_UNICODE),
                        loadFailed: @json(__('Availability could not be loaded.'), JSON_UNESCAPED_UNICODE),
                        choose: @json(__('Choose an available time.'), JSON_UNESCAPED_UNICODE),
                        none: @json(__('No times are available for this date.'), JSON_UNESCAPED_UNICODE),
                        error: @json(__('Unable to load available times. Please try again.'), JSON_UNESCAPED_UNICODE),
                    };
                    const calendarMessages = {
                        loading: @json(__('Checking appointment availability...'), JSON_UNESCAPED_UNICODE),
                        ready: "",
                        error: @json(__('Could not check appointment availability. Please refresh the page.'), JSON_UNESCAPED_UNICODE),
                    };
                    const appointmentMessages = {
                        dateEmpty: @json(__('Choose a date on the calendar'), JSON_UNESCAPED_UNICODE),
                        timeEmpty: @json(__('Select a date first'), JSON_UNESCAPED_UNICODE),
                        selectTime: @json(__('Choose an available time'), JSON_UNESCAPED_UNICODE),
                        chooseTime: @json(__('Choose a time'), JSON_UNESCAPED_UNICODE),
                        changeTime: @json(__('Change time'), JSON_UNESCAPED_UNICODE),
                    };

                    function formatAppointmentDate(date) {
                        const [year, month, day] = date.split("-").map(Number);
                        return new Intl.DateTimeFormat(calendarLocale, {
                            weekday: "long",
                            year: "numeric",
                            month: "long",
                            day: "numeric",
                            timeZone: "UTC",
                        }).format(new Date(Date.UTC(year, month - 1, day, 12)));
                    }

                    function formatAppointmentTime(time) {
                        const [hour, minute] = time.split(":").map(Number);
                        return new Intl.DateTimeFormat(calendarLocale, {
                            hour: "numeric",
                            minute: "2-digit",
                            timeZone: "UTC",
                        }).format(new Date(Date.UTC(1970, 0, 1, hour, minute))) + " MYT";
                    }

                    function updateAppointmentSummary() {
                        const hasDate = Boolean(bookingDate.value);
                        const hasTime = Boolean(bookingTime.value);
                        bookingDateSummary.textContent = hasDate
                            ? formatAppointmentDate(bookingDate.value)
                            : appointmentMessages.dateEmpty;
                        bookingTimeSummary.textContent = hasTime
                            ? formatAppointmentTime(bookingTime.value)
                            : hasDate ? appointmentMessages.selectTime : appointmentMessages.timeEmpty;
                        bookingDateCard.classList.toggle("is-selected", hasDate);
                        bookingTimeCard.classList.toggle("is-selected", hasTime);
                        changeTimeButton.disabled = !hasDate;
                        changeTimeButton.textContent = hasTime
                            ? appointmentMessages.changeTime
                            : appointmentMessages.chooseTime;
                    }

                    let availableAppointmentDates = new Set();
                    let calendarAvailabilityFailed = false;
                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        locale: calendarLocale,
                        timeZone: "UTC",
                        now: @json($calendarNow),
                        initialView: "dayGridMonth",
                        weekends: false,
                        events: availabilityEventsUrl,
                        loading: function (isLoading) {
                            if (isLoading) {
                                calendarAvailabilityFailed = false;
                                calendarAvailabilityStatus.textContent = calendarMessages.loading;
                            } else if (!calendarAvailabilityFailed) {
                                calendarAvailabilityStatus.textContent = calendarMessages.ready;
                            }
                        },
                        eventSourceFailure: function () {
                            calendarAvailabilityFailed = true;
                            calendarAvailabilityStatus.textContent = calendarMessages.error;
                        },
                        eventsSet: function (events) {
                            availableAppointmentDates = new Set(events
                                .filter(function (event) {
                                    return event.extendedProps.available;
                                })
                                .map(function (event) {
                                    return event.startStr.slice(0, 10);
                                }));
                            calendarEl.querySelectorAll(".fc-daygrid-day[data-date]").forEach(function (cell) {
                                const isAvailable = availableAppointmentDates.has(cell.dataset.date);
                                cell.classList.toggle("booking-date-available", isAvailable);
                                cell.classList.toggle("booking-date-unavailable", !isAvailable);
                                cell.setAttribute("aria-disabled", String(!isAvailable));
                            });
                        },
                        dayCellClassNames: function (info) {
                            const date = info.date;
                            const cellDate = [
                                date.getFullYear(),
                                String(date.getMonth() + 1).padStart(2, "0"),
                                String(date.getDate()).padStart(2, "0"),
                            ].join("-");
                            const classNames = [];

                            if (!availableAppointmentDates.has(cellDate)) {
                                classNames.push("booking-date-unavailable");
                            } else {
                                classNames.push("booking-date-available");
                            }
                            if (cellDate === selectedAppointmentDate) {
                                classNames.push("booking-date-selected");
                            }

                            return classNames;
                        },
                        dateClick: async function (info) {
                            if (!availableAppointmentDates.has(info.dateStr)) {
                                return;
                            }
                            calendar.select(info.dateStr);
                            calendarEl.querySelector(".booking-date-selected")?.classList.remove("booking-date-selected");
                            selectedAppointmentDate = info.dateStr;
                            info.dayEl.classList.add("booking-date-selected");
                            bookingDate.value = info.dateStr;
                            await loadAvailableTimes(info.dateStr, true);
                        }
                    });

                    async function loadAvailableTimes(date, clearSelection) {
                        const request = ++availabilityRequest;
                        if (clearSelection) {
                            bookingTime.value = "";
                        }
                        appointmentSelectionError.hidden = true;
                        updateAppointmentSummary();
                        availableTimes.replaceChildren();
                        availabilityMessage.textContent = availabilityMessages.loading;
                        timesModal.show();

                        try {
                            const response = await fetch(availabilityUrl + "?date=" + encodeURIComponent(date), {
                                headers: { "Accept": "application/json" }
                            });
                            if (!response.ok) {
                                throw new Error(availabilityMessages.loadFailed);
                            }

                            const data = await response.json();
                            if (request !== availabilityRequest) {
                                return;
                            }
                            if (bookingTime.value && !data.slots.includes(bookingTime.value)) {
                                bookingTime.value = "";
                                updateAppointmentSummary();
                            }

                            renderTimes(data.slots);
                            availabilityMessage.textContent = data.slots.length
                                ? availabilityMessages.choose
                                : availabilityMessages.none;
                        } catch (error) {
                            if (request === availabilityRequest) {
                                availabilityMessage.textContent = availabilityMessages.error;
                            }
                        }
                    }

                    changeTimeButton.addEventListener("click", function () {
                        if (bookingDate.value) {
                            loadAvailableTimes(bookingDate.value, false);
                        }
                    });
                    bookingForm.addEventListener("submit", function (event) {
                        if (serviceSelect.value === deviceDetailsServiceId) {
                            const outsideLocation = Object.keys(locationInputs).find(function (mode) {
                                const coordinates = getLocationCoordinates(mode);
                                return coordinates && !isWithinKamparServiceArea(coordinates);
                            });
                            if (outsideLocation) {
                                event.preventDefault();
                                locationMode = outsideLocation;
                                showLocationForMode();
                                return;
                            }
                        }

                        if (bookingDate.value && bookingTime.value) {
                            return;
                        }

                        event.preventDefault();
                        appointmentSelectionError.hidden = false;
                        if (bookingDate.value) {
                            changeTimeButton.focus();
                        } else {
                            calendarEl.scrollIntoView({ behavior: "smooth", block: "center" });
                        }
                    });

                    calendar.render();
                    if (bookingDate.value) {
                        calendar.select(bookingDate.value);
                    }
                    updateAppointmentSummary();
                });
            </script>
        @endpush