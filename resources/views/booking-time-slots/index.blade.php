@extends('layouts.app')

@section('title', 'Available Booking Times')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Bookings" title="Available booking times"
        description="Manage the recurring weekly times customers can book. Times are shown in Malaysia time (MYT).">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('booking-time-slots.create') }}" id="add-available-time">
                <span class="bi bi-plus me-2"></span>Add available time
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="alert alert-success d-none" id="slot-feedback" role="status" aria-live="polite"></div>
    <div class="card">
        <div class="card-body">
            <div id="availability-calendar" aria-label="Recurring available booking times"></div>
        </div>
    </div>

    <div class="modal fade" id="slot-modal" tabindex="-1" aria-labelledby="slot-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="slot-form" method="POST" action="{{ route('booking-time-slots.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="slot-modal-title">Add available time</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" id="slot-form-errors" role="alert"></div>
                        <div class="mb-3">
                            <label class="form-label" for="slot-day">Day of week</label>
                            <select class="form-select" id="slot-day" name="day_of_week" required>
                                @foreach ($days as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="slot-time">Start time (Malaysia time)</label>
                            <input class="form-control" id="slot-time" name="start_time" type="time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-danger me-auto d-none" id="delete-available-time" type="button">Delete</button>
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" id="save-available-time" type="submit">Create available time</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    #availability-calendar .fc-v-event .fc-event-main {
        padding-left: 1rem;
    }

    #availability-calendar .fc-v-event .fc-event-main::after {
        background-color: #00875a !important;
    }
</style>
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const calendarElement = document.getElementById('availability-calendar');
        if (!calendarElement || !window.FullCalendar) return;

        const createButton = document.getElementById('add-available-time');
        const form = document.getElementById('slot-form');
        const modalElement = document.getElementById('slot-modal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const modalTitle = document.getElementById('slot-modal-title');
        const daySelect = document.getElementById('slot-day');
        const timeInput = document.getElementById('slot-time');
        const deleteButton = document.getElementById('delete-available-time');
        const saveButton = document.getElementById('save-available-time');
        const errorBox = document.getElementById('slot-form-errors');
        let feedback = document.getElementById('slot-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = 'slot-feedback';
            feedback.className = 'alert alert-success d-none';
            feedback.setAttribute('role', 'status');
            feedback.setAttribute('aria-live', 'polite');
            (calendarElement.closest('.card') || calendarElement.parentElement).before(feedback);
        }
        const csrfToken = form.querySelector('[name="_token"]').value;
        let currentEventId = null;
        let currentDeleteUrl = null;
        let isSaving = false;

        function clearError() {
            if (!errorBox) return;

            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }

        function showError(message) {
            if (!errorBox) return;

            errorBox.textContent = message;
            errorBox.classList.remove('d-none');
        }

        function showFeedback(message) {
            feedback.textContent = message;
            feedback.classList.remove('d-none');
        }

        function openCreateModal() {
            form.reset();
            daySelect.value = '1';
            form.action = createButton.href;
            form.dataset.method = 'POST';
            modalTitle.textContent = 'Add available time';
            saveButton.textContent = 'Create available time';
            deleteButton?.classList.add('d-none');
            currentEventId = null;
            currentDeleteUrl = null;
            feedback.classList.add('d-none');
            clearError();
            modal.show();
        }

        async function sendRequest(url, method, payload) {
            const response = await fetch(url, {
                method: method,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload ? JSON.stringify(payload) : undefined,
            });
            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error(`Could not save available time (HTTP ${response.status}).`);
            }
            if (!response.ok) {
                const validationErrors = Object.values(result.errors || {}).flat();
                throw new Error(validationErrors.join(' ') || result.message || `Could not save available time (HTTP ${response.status}).`);
            }

            return result;
        }

        function replaceCalendarEvent(event) {
            calendar.getEventById(String(event.id))?.remove();
            calendar.addEvent(event);
        }

        createButton.addEventListener('click', (event) => {
            event.preventDefault();
            openCreateModal();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (isSaving) return;

            isSaving = true;
            saveButton.disabled = true;
            deleteButton.disabled = true;
            clearError();
            const method = form.dataset.method || 'POST';
            const originalLabel = saveButton.textContent;
            saveButton.textContent = 'Saving…';
            try {
                const result = await sendRequest(form.action, method, {
                    day_of_week: daySelect.value,
                    start_time: timeInput.value,
                });
                replaceCalendarEvent(result.event);
                modal.hide();
                showFeedback(result.message);
            } catch (error) {
                showError(error instanceof Error ? error.message : 'Could not save available time.');
            } finally {
                isSaving = false;
                saveButton.disabled = false;
                deleteButton.disabled = false;
                saveButton.textContent = originalLabel;
            }
        });

        deleteButton.addEventListener('click', async () => {
            if (!currentEventId || !currentDeleteUrl || isSaving) return;
            if (!window.confirm('Delete this recurring available time?')) return;

            isSaving = true;
            saveButton.disabled = true;
            deleteButton.disabled = true;
            clearError();
            try {
                const result = await sendRequest(currentDeleteUrl, 'DELETE');
                calendar.getEventById(String(result.id))?.remove();
                modal.hide();
                showFeedback(result.message);
            } catch (error) {
                showError(error instanceof Error ? error.message : 'Could not delete available time.');
            } finally {
                isSaving = false;
                saveButton.disabled = false;
                deleteButton.disabled = false;
            }
        });

        const calendar = new FullCalendar.Calendar(calendarElement, {
            timeZone: 'UTC',
            now: @json($calendarNow),
            initialView: 'timeGridWeek',
            height: 'auto',
            nowIndicator: true,
            allDaySlot: false,
            slotMinTime: '08:00:00',
            slotMaxTime: '19:00:00',
            dayHeaderFormat: { weekday: 'short', day: 'numeric' },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            },
            events: @json($events),
            eventDidMount: (info) => {
                const backgroundColor = info.event.backgroundColor;
                const textColor = info.event.textColor;
                if (backgroundColor) {
                    info.el.style.setProperty('background-color', backgroundColor, 'important');
                    info.el.style.setProperty('border-color', info.event.borderColor || backgroundColor, 'important');
                }
                info.el.style.setProperty('opacity', '1', 'important');
                if (textColor) {
                    info.el.style.setProperty('color', textColor, 'important');
                    info.el.querySelector('.fc-event-main')?.style.setProperty('color', textColor, 'important');
                    info.el.querySelectorAll('.fc-event-title, .fc-event-time').forEach((element) => {
                        element.style.setProperty('color', textColor, 'important');
                    });
                }
            },
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short',
            },
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                const event = info.event;
                const details = event.extendedProps;
                if (!details.updateUrl || !details.deleteUrl) return;

                currentEventId = event.id;
                currentDeleteUrl = details.deleteUrl;
                form.action = details.updateUrl;
                form.dataset.method = 'PUT';
                daySelect.value = details.dayOfWeek;
                timeInput.value = details.startTime;
                modalTitle.textContent = 'Edit available time';
                saveButton.textContent = 'Save changes';
                deleteButton?.classList.remove('d-none');
                feedback.classList.add('d-none');
                clearError();
                modal.show();
            },
        });

        calendar.render();
    });
</script>
@endpush
