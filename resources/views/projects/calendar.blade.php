@extends('layouts.app')

@section('title', 'Calendar')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Projects" title="Calendar"
        description="See consultations, mentoring, workshops, reviews, and technical clinics across every project."
        :back-url="route('projects.index')" back-label="All projects">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('projects.index') }}">Browse projects</a>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#create-event-modal">
                <i class="fas fa-calendar-plus me-2"></i>Add event
            </button>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <section class="card">
        <div class="card-body">
            <div id="project-calendar" aria-label="Project events calendar"></div>
        </div>
    </section>
    @include('projects._event-modal', ['projects' => $projects])
</div>
@endsection

@push('scripts')
<style>
    #project-calendar .fc-project-event-project {
        font-weight: 700;
    }
</style>
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const calendarElement = document.getElementById('project-calendar');
        if (!calendarElement || !window.FullCalendar) return;

        const calendar = new FullCalendar.Calendar(calendarElement, {
            initialView: 'dayGridMonth',
            initialDate: @json($monthStart->toDateString()),
            height: 'auto',
            nowIndicator: true,
            navLinks: true,
            dayMaxEvents: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
            },
            dateClick: (info) => {
                const date = info.dateStr.slice(0, 10);
                document.getElementById('calendar-event-start').value = `${date}T09:00`;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('create-event-modal')).show();
            },
            events: @json(route('projects.calendar.events')),
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short',
            },
            eventContent: (info) => {
                const content = document.createElement('span');
                const project = document.createElement('span');
                const title = document.createElement('span');

                project.className = 'fc-project-event-project';
                project.textContent = info.event.extendedProps.project || 'General event';
                title.textContent = `: ${info.event.title}`;
                content.append(project, title);

                return { domNodes: [content] };
            },
            eventDidMount: (info) => {
                const { type, location } = info.event.extendedProps;
                info.el.title = [
                    info.event.extendedProps.project || 'General event',
                    type,
                    location,
                ].filter(Boolean).join(' · ');
            },
        });

        calendar.render();
    });
</script>
@endpush
