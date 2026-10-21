<div class="modal fade" id="event-details-modal" tabindex="-1" aria-labelledby="event-details-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="event-details-title" data-event-title>Calendar event</h2>
                    <span class="small text-body-secondary" data-event-type></span>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4" data-event-project-label>Project</dt>
                    <dd class="col-sm-8"><a data-event-project-link class="d-none"></a><span data-event-project></span></dd>
                    <dt class="col-sm-4 d-none" data-event-reference-label>Reference</dt>
                    <dd class="col-sm-8 d-none" data-event-reference></dd>
                    <dt class="col-sm-4 d-none" data-event-status-label>Status</dt>
                    <dd class="col-sm-8 d-none" data-event-status></dd>
                    <dt class="col-sm-4">Starts</dt>
                    <dd class="col-sm-8" data-event-start></dd>
                    <dt class="col-sm-4">Ends</dt>
                    <dd class="col-sm-8" data-event-end></dd>
                    <dt class="col-sm-4">Location</dt>
                    <dd class="col-sm-8 text-break" data-event-location></dd>
                    <dt class="col-sm-4">Notes</dt>
                    <dd class="col-sm-8" data-event-description style="white-space: pre-wrap"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a class="btn btn-primary d-none" data-event-record-link></a>
                <form method="POST" data-event-delete-form
                    data-event-delete-url="{{ route('events.destroy', ['event' => '__EVENT__']) }}"
                    onsubmit="return confirm('Remove this event?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete event</button>
                </form>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
