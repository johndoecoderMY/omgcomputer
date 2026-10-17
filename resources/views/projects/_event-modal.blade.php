@php($fixedProject = $fixedProject ?? null)
<div class="modal fade" id="create-event-modal" tabindex="-1" aria-labelledby="create-event-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('events.store') }}">
            @csrf
            <input type="hidden" name="return_to" value="{{ $fixedProject ? 'project' : 'calendar' }}">
            @if ($fixedProject)
                <input type="hidden" name="project_id" value="{{ $fixedProject->id }}">
            @endif
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="create-event-title">Add calendar event</h2>
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
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save event</button>
            </div>
        </form>
    </div>
</div>
