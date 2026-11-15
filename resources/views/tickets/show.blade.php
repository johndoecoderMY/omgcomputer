@extends('layouts.app')

@section('title', $ticket->ticket_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/glightbox/glightbox.min.css') }}">
@endpush

@section('content')
    <div class="container-fluid py-3" data-ticket-page data-project-ajax-root>
        <x-page-header eyebrow="Ticket" :title="$ticket->ticket_number"
            :back-url="auth()->user()->hasRole('customer') ? route('tickets.index') : null"
            back-label="All tickets">
            @unless (auth()->user()->hasRole('customer'))
                <x-slot name="actions">
                    <a href="{{ route('tickets.index') }}">
                        <span class="bi bi-arrow-left me-2" aria-hidden="true"></span>All tickets
                    </a>
                    <a href="{{ route('customers.show', $ticket->customer) }}">
                        <span class="bi bi-person me-2" aria-hidden="true"></span>Customer profile
                    </a>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#ticket-workflow-modal">
                        <span class="bi bi-sliders me-2" aria-hidden="true"></span>Edit workflow
                    </button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#ticket-step-modal">
                        <span class="bi bi-plus me-2" aria-hidden="true"></span>Add repair step
                    </button>
                </x-slot>
            @endunless
        </x-page-header>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div id="ticket-status-display">
                @include('tickets.partials.status-badge', ['ticket' => $ticket, 'statuses' => $statuses])
            </div>
            @unless (auth()->user()->hasRole('customer'))
                <span class="badge text-bg-light border" id="ticket-priority-display">
                    Priority: {{ $priorities[$ticket->priority] ?? $ticket->priority }}
                </span>
            @endunless
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Device and reported issue</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Customer</div>
                                @unless (auth()->user()->hasRole('customer'))
                                    <a href="{{ route('customers.show', $ticket->customer) }}">{{ $ticket->customer->name }}</a>
                                @else
                                    {{ $ticket->customer->name }}
                                @endunless
                            </div>
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Device</div>
                                {{ $ticket->device_type ?: 'Device' }} <span id="ticket-device-model">{{ $ticket->device_model }}</span>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Serial number</div>
                                <span id="ticket-serial-number">{{ $ticket->serial_number ?: '—' }}</span>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-body-secondary">Project</div>
                                @if ($ticket->project)
                                    @unless (auth()->user()->hasRole('customer'))
                                        <a href="{{ route('projects.show', $ticket->project) }}">{{ $ticket->project->name }}</a>
                                    @else
                                        {{ $ticket->project->name }}
                                    @endunless
                                @else
                                    —
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="small text-body-secondary">Received</div>
                                {{ $ticket->received_at->format('M j, Y g:i A') }}
                            </div>
                        </div>
                        <div class="small text-body-secondary">Reported issue</div>
                        <p class="text-pre-line mb-0">{{ $ticket->issue_description }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header fw-semibold">Diagnosis and service notes</div>
                    <div class="card-body text-pre-line" id="ticket-diagnosis-display">
                        {{ $ticket->diagnosis ?: 'No diagnosis or service notes recorded.' }}
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header fw-semibold">Repair steps and SOP log</div>
                    <div id="ticket-updates-display">
                        @include('tickets.partials.update-list', ['ticket' => $ticket, 'statuses' => $statuses])
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                @unless (auth()->user()->hasRole('customer'))
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Ticket workflow</div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-body-secondary">Assigned to</span>
                                <span class="text-end" id="ticket-assignees-display">
                                    {{ $ticket->assignees->pluck('name')->join(', ') ?: 'Unassigned' }}
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-body-secondary">Target date</span>
                                <span id="ticket-due-date-display">{{ $ticket->due_at?->format('M j, Y') ?? 'Not set' }}</span>
                            </div>
                        </div>
                    </div>
                @endunless

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold">Pricing and Billing documents</span>
                        @unless (auth()->user()->hasRole('customer'))
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('billing.create', ['type' => 'quotation', 'repair_ticket_id' => $ticket->id, 'ticket_modal' => 1]) }}"
                                data-project-ajax-modal>
                                <span class="bi bi-plus me-1" aria-hidden="true"></span>Create quotation
                            </a>
                        @endunless
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($ticket->billingDocuments as $document)
                            <div class="list-group-item">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2">
                                    <a class="flex-grow-1 text-decoration-none text-reset"
                                        href="{{ route('billing.documents.show', $document) }}">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <span>
                                                <span class="fw-semibold d-block">{{ $document->number }}</span>
                                                <small class="text-body-secondary">
                                                    {{ ucfirst($document->type) }} ·
                                                    <x-status-badge :status="$document->status" :label="ucfirst($document->status)" />
                                                </small>
                                            </span>
                                            <span class="text-end">
                                                <small class="d-block text-body-secondary">Total</small>
                                                <strong>RM {{ number_format((float) $document->total, 2) }}</strong>
                                                @if ($document->type === 'invoice')
                                                    <small class="d-block text-body-secondary">
                                                        Balance RM {{ number_format($document->balance, 2) }}
                                                    </small>
                                                @endif
                                            </span>
                                        </div>
                                    </a>
                                    @unless (auth()->user()->hasRole('customer'))
                                        @if ($document->type === 'quotation' && $document->status === 'accepted')
                                            <form method="POST" action="{{ route('billing.documents.convert', $document) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-primary" type="submit"
                                                    onclick="return confirm('Issue this invoice and deduct listed stock?')">
                                                    <span class="bi bi-receipt me-1" aria-hidden="true"></span>Create invoice
                                                </button>
                                            </form>
                                        @endif
                                    @endunless
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-body-secondary">No quotations or invoices linked yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    @unless (auth()->user()->hasRole('customer'))
        <div class="modal fade" id="ticket-workflow-modal" tabindex="-1" aria-labelledby="ticket-workflow-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" data-ticket-ajax-form>
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="ticket-workflow-title">Edit ticket details</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" role="alert" data-ticket-form-error></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-status">Status</label>
                                    <select class="form-select" id="workflow-status" name="status" required>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-priority">Priority</label>
                                    <select class="form-select" id="workflow-priority" name="priority" required>
                                        @foreach ($priorities as $value => $label)
                                            <option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-device-model">Device make / model</label>
                                    <input class="form-control" id="workflow-device-model" name="device_model"
                                        maxlength="255" value="{{ $ticket->device_model }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-serial-number">Serial number</label>
                                    <input class="form-control" id="workflow-serial-number" name="serial_number"
                                        maxlength="255" value="{{ $ticket->serial_number }}">
                                </div>
                                @role('admin')
                                    <div class="col-12">
                                        <label class="form-label" for="workflow-assignees">Assign people</label>
                                        <input type="hidden" name="assignees_present" value="1">
                                        <select class="form-select js-user-search" id="workflow-assignees" name="assignees[]"
                                            multiple data-placeholder="Search and select people">
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}" @selected($ticket->assignees->contains('id', $user->id))>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endrole
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-due-at">Target date</label>
                                    <input class="form-control" id="workflow-due-at" name="due_at" type="date"
                                        value="{{ $ticket->due_at?->format('Y-m-d') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="workflow-diagnosis">Diagnosis / work performed</label>
                                    <textarea class="form-control" id="workflow-diagnosis" name="diagnosis" rows="5"
                                        maxlength="5000">{{ $ticket->diagnosis }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Save workflow</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ticket-step-modal" tabindex="-1" aria-labelledby="ticket-step-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form id="ticket-step-form" class="modal-content" method="POST"
                    action="{{ route('tickets.steps.store', $ticket) }}" enctype="multipart/form-data"
                    data-ticket-ajax-form>
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="ticket-step-title">Add repair step</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" role="alert" data-ticket-form-error></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="step-title">Step performed</label>
                                <input class="form-control" id="step-title" name="title" maxlength="180"
                                    placeholder="Example: Inspect charging port" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="step-status">Ticket status after step</label>
                                <select class="form-select" id="step-status" name="status" required>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="step-notes">Procedure and findings</label>
                                <textarea class="form-control" id="step-notes" name="notes" rows="3" maxlength="5000"
                                    placeholder="Record checks performed, result, parts used, and customer approval. Keep sensitive customer data out of photos."></textarea>
                                <div class="form-text">Internal staff notes; not shown on public repair tracking.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="step-customer-details">Customer-facing update <span class="text-body-secondary">(optional)</span></label>
                                <textarea class="form-control" id="step-customer-details" name="customer_details" rows="3" maxlength="5000"
                                    placeholder="Share a brief, customer-safe summary of this repair step."></textarea>
                                <div class="form-text">This summary appears to customers on the public repair tracking page.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-text">Save the repair step first, then add photos one at a time with an individual caption.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Add repair step</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/glightbox/glightbox.min.js') }}"></script>
    <script>
        (() => {
            const getPage = () => document.querySelector('[data-ticket-page]');
            if (!getPage()) return;

            let lightbox;
            const initializeLightbox = () => {
                lightbox?.destroy();
                lightbox = window.GLightbox ? window.GLightbox({ selector: '.attachment-lightbox' }) : null;
            };
            const initializeStepCarousels = (container = getPage()) => {
                if (!container) return;
                container.querySelectorAll('[data-ticket-step-carousel]').forEach((carousel) => {
                    bootstrap.Carousel.getOrCreateInstance(carousel, { interval: false, touch: true });
                });
            };
            const showToast = (message, type = 'success') => {
                if (window.showAppToast) {
                    window.showAppToast(message, type, 'Repair ticket');
                }
            };
            const hideModal = (modal) => new Promise((resolve) => {
                if (!modal.classList.contains('show')) {
                    resolve();
                    return;
                }
                modal.addEventListener('hidden.bs.modal', resolve, { once: true });
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            });

            initializeLightbox();
            initializeStepCarousels();

            document.addEventListener('paste', (event) => {
                if (!(event.target instanceof HTMLTextAreaElement)
                    || !event.target.matches('[data-ticket-step-photo-paste]')) {
                    return;
                }

                event.preventDefault();
                const form = event.target.closest('form[data-ticket-step-photo-form]');
                const input = form?.querySelector('input[type="file"][name="photos[]"]');
                const errorBox = form?.querySelector('[data-ticket-form-error]');
                if (!(input instanceof HTMLInputElement) || !errorBox) return;

                const clipboardItems = Array.from(event.clipboardData?.items || []);
                const imageItem = clipboardItems.find((item) => item.kind === 'file' && item.type.startsWith('image/'));
                const clipboardFiles = Array.from(event.clipboardData?.files || []);
                const sourceFile = imageItem?.getAsFile()
                    || clipboardFiles.find((file) => file.type.startsWith('image/'));

                const extensions = {
                    'image/jpeg': 'jpg',
                    'image/png': 'png',
                    'image/webp': 'webp',
                };
                const extension = sourceFile ? extensions[sourceFile.type] : null;
                if (!sourceFile || !extension) {
                    errorBox.textContent = sourceFile
                        ? 'Only JPEG, PNG, and WebP images can be pasted. Choose a supported photo file instead.'
                        : 'The clipboard does not contain an image. Copy a photo or choose a file.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(new File([sourceFile], `pasted-repair-photo.${extension}`, {
                        type: sourceFile.type,
                        lastModified: Date.now(),
                    }));
                    input.files = transfer.files;
                } catch (error) {
                    console.error('Could not attach the pasted repair-step photo.', error);
                    errorBox.textContent = 'Could not attach the pasted photo. Choose a file from your device instead.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                errorBox.textContent = '';
                errorBox.classList.add('d-none');
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            document.addEventListener('submit', async (event) => {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement)
                        || !form.matches('[data-ticket-ajax-form]')
                        || !form.closest('[data-ticket-page]')) {
                        return;
                    }

                    event.preventDefault();
                    const errorBox = form.querySelector('[data-ticket-form-error]')
                        || form.closest('.modal')?.querySelector('[data-ticket-form-error]');
                    const submitter = event.submitter instanceof HTMLButtonElement
                        || event.submitter instanceof HTMLInputElement
                        ? event.submitter
                        : null;
                    const submitButton = submitter
                        || form.querySelector('button[type="submit"], input[type="submit"]');
                    const originalLabel = submitButton instanceof HTMLButtonElement
                        ? submitButton.textContent.trim()
                        : '';
                    if (errorBox) errorBox.classList.add('d-none');
                    if (submitButton) {
                        submitButton.disabled = true;
                        if (submitButton instanceof HTMLButtonElement) submitButton.textContent = 'Saving…';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: form.method.toUpperCase(),
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            const validationErrors = Object.values(result.errors || {}).flat().join(' ');
                            throw new Error(validationErrors || result.message || `Could not save changes (HTTP ${response.status}).`);
                        }

                        const page = form.closest('[data-ticket-page]');
                        const statusDisplay = page.querySelector('#ticket-status-display');
                        const updatesDisplay = page.querySelector('#ticket-updates-display');
                        if (!statusDisplay || !updatesDisplay) {
                            throw new Error('Ticket details could not be refreshed. Reload the page to see the latest changes.');
                        }
                        const modal = form.closest('.modal');
                        if (!modal) {
                            throw new Error('The ticket form is not inside a dialog. Reload the page and try again.');
                        }
                        await hideModal(modal);
                        if (form.matches('[data-ticket-step-photo-form]')) {
                            form.reset();
                            form.querySelectorAll('[data-image-upload-selection]').forEach((selection) => {
                                selection.textContent = '';
                            });
                        }
                        statusDisplay.innerHTML = result.status_html;
                        updatesDisplay.querySelectorAll('[data-ticket-step-carousel]').forEach((carousel) => {
                            bootstrap.Carousel.getInstance(carousel)?.dispose();
                        });
                        updatesDisplay.innerHTML = result.updates_html;
                        initializeStepCarousels(updatesDisplay);
                        if (Object.prototype.hasOwnProperty.call(result, 'diagnosis')) {
                            page.querySelector('#ticket-diagnosis-display').textContent =
                                result.diagnosis || 'No diagnosis or service notes recorded.';
                            page.querySelector('#ticket-priority-display').textContent = `Priority: ${result.priority_label}`;
                            page.querySelector('#ticket-assignees-display').textContent = result.assignees.join(', ') || 'Unassigned';
                            page.querySelector('#ticket-due-date-display').textContent = result.due_at;
                            page.querySelector('#workflow-status').value = result.status;
                            page.querySelector('#workflow-priority').value = result.priority;
                            page.querySelector('#workflow-due-at').value = result.due_at_value;
                            page.querySelector('#workflow-diagnosis').value = result.diagnosis || '';
                            page.querySelector('#workflow-device-model').value = result.device_model || '';
                            page.querySelector('#workflow-serial-number').value = result.serial_number || '';
                            page.querySelector('#ticket-device-model').textContent = result.device_model || '';
                            page.querySelector('#ticket-serial-number').textContent = result.serial_number || '—';
                            const workflowAssignees = page.querySelector('#workflow-assignees');
                            if (workflowAssignees) {
                                workflowAssignees.value = result.assignee_ids.map(String);
                                window.jQuery(workflowAssignees).trigger('change');
                            }
                        }
                        if (result.status) {
                            const stepStatus = page.querySelector('#step-status');
                            if (stepStatus) stepStatus.value = result.status;
                            const workflowStatus = page.querySelector('#workflow-status');
                            if (workflowStatus) workflowStatus.value = result.status;
                        }

                        initializeLightbox();
                        if (form.id === 'ticket-step-form') {
                            form.reset();
                            const stepStatus = page.querySelector('#step-status');
                            if (stepStatus) stepStatus.value = result.status || stepStatus.value;
                        }
                        showToast(result.message || 'Ticket updated.');
                    } catch (error) {
                        const message = error.message || 'Could not save changes. Please try again.';
                        if (errorBox) {
                            errorBox.textContent = message;
                            errorBox.classList.remove('d-none');
                        } else {
                            showToast(message, 'danger');
                        }
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = false;
                            if (submitButton instanceof HTMLButtonElement) submitButton.textContent = originalLabel;
                        }
                    }
                }, true);

            const workflowAssignees = document.getElementById('workflow-assignees');
            if (workflowAssignees && window.initializeProjectAjaxControls) {
                window.initializeProjectAjaxControls(workflowAssignees.closest('.modal'));
            }

            document.addEventListener('project:content-updated', () => {
                initializeLightbox();
                initializeStepCarousels();
            });
        })();
    </script>
@endpush
