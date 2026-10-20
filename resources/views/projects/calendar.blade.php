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

    <div class="card p-2">
        <div class="card-body">
            <div id="project-calendar" aria-label="Project events calendar"></div>
        </div>
    </div>
    @include('projects._event-details-modal')
    @include('projects._event-modal', ['projects' => $projects])
</div>
@endsection

@push('scripts')
<style>
    #project-calendar .fc-project-event-project {
        font-weight: 700;
        flex: 0 1 auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #project-calendar .fc-project-event-title {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #project-calendar .fc-project-event-content {
        display: flex;
        min-width: 0;
        overflow: hidden;
    }

    #project-calendar .fc-daygrid-event .fc-event-main,
    #project-calendar .fc-daygrid-event .fc-event-title-container {
        min-width: 0;
        overflow: hidden;
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
            moreLinkClick: 'popover',
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

                content.className = 'fc-project-event-content';
                project.className = 'fc-project-event-project';
                project.textContent = info.event.extendedProps.project || 'General event';
                title.textContent = `: ${info.event.title}`;
                title.className = 'fc-project-event-title';
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
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                const event = info.event;
                const props = event.extendedProps;
                const modal = document.getElementById('event-details-modal');
                const projectLink = modal.querySelector('[data-event-project-link]');
                const deleteForm = modal.querySelector('[data-event-delete-form]');

                modal.querySelector('[data-event-title]').textContent = event.title;
                modal.querySelector('[data-event-type]').textContent = props.type || 'Event';
                modal.querySelector('[data-event-project]').textContent = props.project || 'General event';
                projectLink.classList.toggle('d-none', !props.project_url);
                if (props.project_url) {
                    projectLink.href = props.project_url;
                    projectLink.textContent = props.project;
                }
                modal.querySelector('[data-event-start]').textContent = event.start
                    ? FullCalendar.formatDate(event.start, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
                    : '—';
                modal.querySelector('[data-event-end]').textContent = event.end
                    ? FullCalendar.formatDate(event.end, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
                    : '—';
                modal.querySelector('[data-event-location]').textContent = props.location || '—';
                modal.querySelector('[data-event-description]').textContent = props.description || '—';
                deleteForm.action = deleteForm.dataset.eventDeleteUrl.replace('__EVENT__', encodeURIComponent(event.id));

                bootstrap.Modal.getOrCreateInstance(modal).show();
            },
        });

        calendar.render();
    });
</script>
@endpush
