@extends('layouts.app')

@section('title', 'Available Booking Times')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Bookings" title="Available booking times"
        description="Manage the recurring weekly times customers can book. Times are shown in Malaysia time (MYT).">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('booking-time-slots.create') }}">
                <span class="fas fa-plus me-2"></span>Add available time
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div id="availability-calendar" aria-label="Recurring available booking times"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const calendarElement = document.getElementById('availability-calendar');
        if (!calendarElement || !window.FullCalendar) return;

        const calendar = new FullCalendar.Calendar(calendarElement, {
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
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short',
            },
            eventClick: (info) => {
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.location.assign(info.event.url);
                }
            },
        });

        calendar.render();
    });
</script>
@endpush
