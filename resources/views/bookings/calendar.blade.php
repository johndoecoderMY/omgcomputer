@extends('layouts.app')

@section('title', 'Book Calendar')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Bookings" title="Book Calendar"
            :description="$customerView ? 'View your scheduled appointments.' : 'Review customer appointments and their current status.'"
            :back-url="route('bookings.index')" back-label="Customer bookings">
        </x-page-header>

        <div class="card p-2">
            <div class="card-body">
                <div id="bookings-calendar" aria-label="Customer bookings calendar"></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="booking-details-modal" tabindex="-1" aria-labelledby="booking-details-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="booking-details-title" data-booking-title>Appointment</h2>
                        <span class="small text-body-secondary" data-booking-reference></span>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status</dt><dd class="col-sm-8" data-booking-status></dd>
                        <dt class="col-sm-4">Customer</dt><dd class="col-sm-8" data-booking-customer></dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8 text-break" data-booking-email></dd>
                        <dt class="col-sm-4">Phone</dt><dd class="col-sm-8" data-booking-phone></dd>
                        <dt class="col-sm-4">Service</dt><dd class="col-sm-8" data-booking-service></dd>
                        <dt class="col-sm-4">Device</dt><dd class="col-sm-8" data-booking-device></dd>
                        <dt class="col-sm-4">Starts</dt><dd class="col-sm-8" data-booking-start></dd>
                        <dt class="col-sm-4">Ends</dt><dd class="col-sm-8" data-booking-end></dd>
                        <dt class="col-sm-4">Request notes</dt>
                        <dd class="col-sm-8" data-booking-notes style="white-space: pre-wrap"></dd>
                        <dt class="col-sm-4 d-none" data-booking-customer-link-label>Customer record</dt>
                        <dd class="col-sm-8 d-none" data-booking-customer-link-container>
                            <a data-booking-customer-link></a>
                        </dd>
                        </dl>
                </div>
                <div class="modal-footer">
                        <a class="btn btn-primary d-none" data-booking-open-link>Open booking</a>
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const calendarElement = document.getElementById('bookings-calendar');
            if (!calendarElement || !window.FullCalendar) return;

            const calendar = new FullCalendar.Calendar(calendarElement, {
                initialView: 'dayGridMonth',
                height: 'auto',
                nowIndicator: true,
                navLinks: true,
                dayMaxEvents: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
                },
                events: @json(route('bookings.calendar.events')),
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short',
                },
                eventDidMount: (info) => {
                    const { backgroundColor, borderColor, textColor } = info.event;
                    if (backgroundColor) {
                        info.el.style.setProperty('background-color', backgroundColor, 'important');
                        info.el.style.setProperty('border-color', borderColor || backgroundColor, 'important');
                    }
                    if (textColor) {
                        info.el.style.setProperty('color', textColor, 'important');
                        info.el.querySelector('.fc-event-main')?.style.setProperty('color', textColor, 'important');
                        info.el.querySelectorAll('.fc-event-title, .fc-event-time').forEach((element) => {
                            element.style.setProperty('color', textColor, 'important');
                        });
                    }
                    info.el.title = [
                        info.event.extendedProps.customer,
                        info.event.extendedProps.service,
                        info.event.extendedProps.status,
                    ].filter(Boolean).join(' · ');
                },
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    const event = info.event;
                    const details = event.extendedProps;
                    const modal = document.getElementById('booking-details-modal');
                    const setText = (selector, value) => {
                        modal.querySelector(selector).textContent = value || '—';
                    };

                    setText('[data-booking-title]', details.service);
                    setText('[data-booking-reference]', details.reference);
                    setText('[data-booking-status]', details.status);
                    setText('[data-booking-customer]', details.customer);
                    setText('[data-booking-email]', details.email);
                    setText('[data-booking-phone]', details.phone);
                    setText('[data-booking-service]', details.service);
                    setText('[data-booking-device]', details.device);
                    setText('[data-booking-start]', event.start
                        ? FullCalendar.formatDate(event.start, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
                        : null);
                    setText('[data-booking-end]', event.end
                        ? FullCalendar.formatDate(event.end, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
                        : null);
                    setText('[data-booking-notes]', details.notes);
                    const customerLink = modal.querySelector('[data-booking-customer-link]');
                    const customerLinkLabel = modal.querySelector('[data-booking-customer-link-label]');
                    const customerLinkContainer = modal.querySelector('[data-booking-customer-link-container]');
                    const openLink = modal.querySelector('[data-booking-open-link]');
                    customerLinkLabel.classList.toggle('d-none', !details.customer_url);
                    customerLinkContainer.classList.toggle('d-none', !details.customer_url);
                    if (details.customer_url) {
                        customerLink.href = details.customer_url;
                        customerLink.textContent = details.customer;
                    }
                    openLink.classList.toggle('d-none', !details.booking_url);
                    if (details.booking_url) openLink.href = details.booking_url;
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                },
            });

            calendar.render();
        });
    </script>
@endpush
