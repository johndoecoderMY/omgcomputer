@php($fixedProject = $fixedProject ?? null)
<div class="modal fade" id="create-event-modal" tabindex="-1" aria-labelledby="create-event-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('events.store') }}" @if ($fixedProject) data-project-event-form @endif>
            @csrf
            <input type="hidden" name="return_to" value="{{ $fixedProject ? 'project' : 'calendar' }}">
            @if ($fixedProject)
                <input type="hidden" name="project_id" value="{{ $fixedProject->id }}">
            @endif
            <div class="modal-header align-items-start">
                @if ($fixedProject)
                    <div class="d-flex align-items-start gap-3">
                        <span class="d-inline-flex flex-shrink-0 align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary p-3">
                            <i class="bi bi-calendar-event fs-4" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="badge text-bg-primary mb-2">Project schedule</span>
                            <h2 class="modal-title fs-5" id="create-event-title">Schedule a project event</h2>
                            <p class="small text-body-secondary mb-0">{{ $fixedProject->name }} · Add a meeting, work session, or milestone.</p>
                        </div>
                    </div>
                @else
                    <h2 class="modal-title fs-5" id="create-event-title">Add calendar event</h2>
                @endif
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <p class="fw-semibold mb-1">Please correct the following:</p>
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @unless ($fixedProject)
                    <div class="mb-3">
                        <label class="form-label" for="calendar-event-project">Project <span class="text-body-secondary">(optional)</span></label>
                        <select class="form-select js-project-search" id="calendar-event-project" name="project_id">
                            <option value="">General event (not linked to a project)</option>
                            @foreach ($projects as $eventProject)
                                <option value="{{ $eventProject->id }}" @selected(old('project_id') == $eventProject->id)>{{ $eventProject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                @if ($fixedProject)
                    <section class="mb-4" aria-labelledby="project-event-details-title">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="badge rounded-pill text-bg-primary">1</span>
                            <div>
                                <h3 class="h6 mb-1" id="project-event-details-title">Event details</h3>
                                <p class="small text-body-secondary mb-0">Give the event a clear name and choose its type.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="calendar-event-title">Event title</label>
                                <input class="form-control" id="calendar-event-title" name="title" value="{{ old('title') }}"
                                    maxlength="180" placeholder="e.g. Weekly project check-in" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="calendar-event-type">Event type</label>
                                <select class="form-select" id="calendar-event-type" name="event_type" required>
                                    @foreach (\App\Models\Event::TYPES as $type => $label)
                                        <option value="{{ $type }}" @selected(old('event_type', 'other') === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>
                    <section class="rounded-3 border bg-body-tertiary p-3 p-md-4 mb-4" aria-labelledby="project-event-time-title">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="badge rounded-pill text-bg-primary">2</span>
                            <div>
                                <h3 class="h6 mb-1" id="project-event-time-title">Choose a time</h3>
                                <p class="small text-body-secondary mb-0">Select a start, then pick a duration or enter an end time.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="calendar-event-start">Starts</label>
                                <input class="form-control" type="datetime-local" id="calendar-event-start" name="starts_at"
                                    value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="calendar-event-end">Ends <span class="text-body-secondary">(optional)</span></label>
                                <input class="form-control" type="datetime-local" id="calendar-event-end" name="ends_at"
                                    value="{{ old('ends_at') }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-label mb-2">Quick duration</div>
                            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Quick event duration">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-event-duration="30" aria-pressed="false">30 min</button>
                                <button class="btn btn-sm btn-primary" type="button" data-event-duration="60" aria-pressed="true">1 hour</button>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-event-duration="120" aria-pressed="false">2 hours</button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-event-duration="none" aria-pressed="false">No end time</button>
                            </div>
                            <div class="form-text">The end defaults to one hour and follows the start time until you change it.</div>
                        </div>
                    </section>
                    <section aria-labelledby="project-event-extra-title">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="badge rounded-pill text-bg-secondary">3</span>
                            <div>
                                <h3 class="h6 mb-1" id="project-event-extra-title">Optional details</h3>
                                <p class="small text-body-secondary mb-0">Add a location, meeting link, or an agenda.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="calendar-event-location">Location or meeting link <span class="text-body-secondary">(optional)</span></label>
                                <input class="form-control" id="calendar-event-location" name="location" value="{{ old('location') }}"
                                    maxlength="180" placeholder="e.g. Meeting room or https://meet.example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="calendar-event-description">Notes or agenda <span class="text-body-secondary">(optional)</span></label>
                                <textarea class="form-control" id="calendar-event-description" name="description" rows="3" maxlength="2000"
                                    placeholder="Add topics, preparation, or follow-up notes">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </section>
                @else
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="calendar-event-title">Title</label>
                            <input class="form-control" id="calendar-event-title" name="title" value="{{ old('title') }}" maxlength="180" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="calendar-event-type">Type</label>
                            <select class="form-select" id="calendar-event-type" name="event_type" required>
                                @foreach (\App\Models\Event::TYPES as $type => $label)
                                    <option value="{{ $type }}" @selected(old('event_type', 'other') === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="calendar-event-start">Starts</label>
                            <input class="form-control" type="datetime-local" id="calendar-event-start" name="starts_at"
                                value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="calendar-event-end">Ends <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" type="datetime-local" id="calendar-event-end" name="ends_at" value="{{ old('ends_at') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="calendar-event-location">Location or meeting link <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="calendar-event-location" name="location" value="{{ old('location') }}" maxlength="180">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="calendar-event-description">Notes <span class="text-body-secondary">(optional)</span></label>
                            <textarea class="form-control" id="calendar-event-description" name="description" rows="3" maxlength="2000">{{ old('description') }}</textarea>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Save event
                </button>
            </div>
        </form>
    </div>
</div>
@if ($fixedProject)
    @push('scripts')
        <script>
            (() => {
                const initializeProjectEventDuration = () => {
                    const form = document.querySelector('[data-project-event-form]');
                    if (!form || form.dataset.durationInitialized === 'true') return;

                    const startInput = form.querySelector('#calendar-event-start');
                    const endInput = form.querySelector('#calendar-event-end');
                    const durationButtons = Array.from(form.querySelectorAll('[data-event-duration]'));
                    if (!startInput || !endInput || durationButtons.length === 0) return;

                    form.dataset.durationInitialized = 'true';
                    let durationMinutes = 60;
                    let endWasManuallyChanged = Boolean(endInput.value);
                    const setDurationSelection = (selectedButton) => {
                        durationButtons.forEach((button) => {
                            const selected = button === selectedButton;
                            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                            button.classList.toggle('btn-primary', selected);
                            button.classList.toggle('btn-outline-primary', !selected && button.dataset.eventDuration !== 'none');
                            button.classList.toggle('btn-outline-secondary', !selected && button.dataset.eventDuration === 'none');
                        });
                    };
                    const updateEndFromStart = () => {
                        if (!startInput.value) return;

                        const start = new Date(startInput.value);
                        if (Number.isNaN(start.getTime())) return;

                        const end = new Date(start.getTime() + durationMinutes * 60 * 1000);
                        const localEnd = new Date(end.getTime() - end.getTimezoneOffset() * 60 * 1000);
                        endInput.value = localEnd.toISOString().slice(0, 16);
                    };
                    const updateStartBounds = () => {
                        endInput.min = startInput.value;
                        if (!endWasManuallyChanged) updateEndFromStart();
                    };

                    startInput.addEventListener('input', updateStartBounds);
                    startInput.addEventListener('change', updateStartBounds);
                    endInput.addEventListener('input', () => {
                        endWasManuallyChanged = true;
                        setDurationSelection(null);
                    });
                    durationButtons.forEach((button) => {
                        button.addEventListener('click', () => {
                            if (button.dataset.eventDuration === 'none') {
                                endWasManuallyChanged = true;
                                endInput.value = '';
                                setDurationSelection(button);
                                return;
                            }

                            durationMinutes = Number(button.dataset.eventDuration);
                            endWasManuallyChanged = false;
                            setDurationSelection(button);
                            updateEndFromStart();
                        });
                    });

                    updateStartBounds();
                };

                document.addEventListener('DOMContentLoaded', initializeProjectEventDuration);
                window.addEventListener('project:content-updated', initializeProjectEventDuration);
            })();
        </script>
    @endpush
@endif
