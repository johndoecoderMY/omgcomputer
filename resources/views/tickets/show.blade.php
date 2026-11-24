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
                    @if ($ticket->customer)
                        <a href="{{ route('customers.show', $ticket->customer) }}">
                            <span class="bi bi-person me-2" aria-hidden="true"></span>Customer profile
                        </a>
                    @elseif (! $ticket->customer_id)
                        <button type="button" data-bs-toggle="modal" data-bs-target="#ticket-create-customer-modal">
                            <span class="bi bi-person-plus me-2" aria-hidden="true"></span>Create customer record
                        </button>
                    @endif
                    <button type="button" data-bs-toggle="modal" data-bs-target="#ticket-workflow-modal">
                        <span class="bi bi-sliders me-2" aria-hidden="true"></span>
                        {{ $ticket->status === 'closed' ? 'Update status' : 'Edit workflow' }}
                    </button>
                    @if ($ticket->status !== 'closed')
                        <button type="button" data-bs-toggle="modal" data-bs-target="#ticket-step-modal">
                            <span class="bi bi-plus me-2" aria-hidden="true"></span>Add repair step
                        </button>
                    @endif
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

        @unless (auth()->user()->hasRole('customer'))
            @if ($ticket->customer?->warning)
                <div class="alert alert-warning" role="alert">
                    <div class="fw-semibold"><span class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></span>Important customer warning</div>
                    <div class="text-pre-line mt-1">{{ $ticket->customer->warning }}</div>
                </div>
            @endif
        @endunless

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Device and reported issue</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Customer</div>
                                @unless (auth()->user()->hasRole('customer'))
                                    @if ($ticket->customer)
                                        <a href="{{ route('customers.show', $ticket->customer) }}">
                                            {{ $ticket->customer->name }}{{ $ticket->customer->nickname ? ' ('.$ticket->customer->nickname.')' : '' }}
                                        </a>
                                    @else
                                        {{ $ticket->face_to_face_name ?: 'Face-to-face customer' }}{{ $ticket->face_to_face_nickname ? ' ('.$ticket->face_to_face_nickname.')' : '' }}
                                    @endif
                                @else
                                    {{ $ticket->customer?->name ?: $ticket->face_to_face_name ?: 'Face-to-face customer' }}
                                @endunless
                            </div>
                            @if (! $ticket->customer_id)
                                <div class="col-md-4">
                                    <div class="small text-body-secondary">Face-to-face contact</div>
                                    {{ $ticket->face_to_face_phone ?: $ticket->face_to_face_email ?: '—' }}
                                    @if ($ticket->face_to_face_phone && $ticket->face_to_face_email)
                                        <div class="small">{{ $ticket->face_to_face_email }}</div>
                                    @endif
                                </div>
                            @endif
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Device</div>
                                {{ $ticket->device_type ?: 'Device' }} <span id="ticket-device-model">{{ $ticket->device_model }}</span>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Serial number</div>
                                <span id="ticket-serial-number">{{ $ticket->serial_number ?: '—' }}</span>
                            </div>
                            @if ($ticket->project)
                                <div class="col-md-6">
                                    <div class="small text-body-secondary">Project</div>
                                    @unless (auth()->user()->hasRole('customer'))
                                        <a href="{{ route('projects.show', $ticket->project) }}">{{ $ticket->project->name }}</a>
                                    @else
                                        {{ $ticket->project->name }}
                                    @endunless
                                </div>
                            @endif
                            <div class="col-md-6">
                                <div class="small text-body-secondary">Received</div>
                                {{ $ticket->received_at->format('M j, Y g:i A') }}
                            </div>
                        </div>
                        <div class="small text-body-secondary">Reported issue</div>
                        <div class="text-pre-line mb-0">{!! app(\App\Support\RepairTicketIssueSanitizer::class)->sanitize($ticket->issue_description) !!}</div>
                        @unless (auth()->user()->hasRole('customer'))
                            @if ($ticket->customer?->notes)
                                <hr>
                                <div class="small text-body-secondary">Internal customer notes</div>
                                <div class="text-pre-line">{{ $ticket->customer->notes }}</div>
                            @endif
                        @endunless
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header fw-semibold">Diagnosis and service notes</div>
                    <div class="card-body text-pre-line" id="ticket-diagnosis-display">
                        {{ $ticket->diagnosis ?: 'No diagnosis or service notes recorded.' }}
                    </div>
                </div>

                @php
                    $pickupAddress = $ticket->pickup_address ?? $ticket->booking?->pickup_address;
                    $dropoffAddress = $ticket->dropoff_address ?? $ticket->booking?->dropoff_address;
                    $pickupLatitude = $ticket->pickup_latitude ?? $ticket->booking?->pickup_latitude;
                    $pickupLongitude = $ticket->pickup_longitude ?? $ticket->booking?->pickup_longitude;
                    $dropoffLatitude = $ticket->dropoff_latitude ?? $ticket->booking?->dropoff_latitude;
                    $dropoffLongitude = $ticket->dropoff_longitude ?? $ticket->booking?->dropoff_longitude;
                    $customerDropoffAtShop = (bool) $ticket->booking?->customer_dropoff_at_shop;
                @endphp
                @if (! auth()->user()->hasRole('customer') || $customerDropoffAtShop || $pickupAddress || $dropoffAddress || $pickupLatitude !== null || $dropoffLatitude !== null)
                    <div class="card mb-3 @if (! $customerDropoffAtShop && ! $pickupAddress && ! $dropoffAddress && $pickupLatitude === null && $dropoffLatitude === null) d-none @endif" id="ticket-location-card">
                        <div class="card-header fw-semibold">{{ $customerDropoffAtShop ? __('Drop-off and collection') : __('Pickup and drop-off') }}</div>
                        @if ($customerDropoffAtShop)
                            <div class="card-body">
                                <div class="alert alert-info mb-0">{{ __('I will drop off and collect the device at the shop.') }}</div>
                            </div>
                        @else
                        <div class="card-body row g-3">
                            <div class="col-md-6">
                                <div class="small text-body-secondary">Pickup address</div>
                                <div class="text-pre-line" id="ticket-pickup-address">{{ $pickupAddress ?: '—' }}</div>
                                <a id="ticket-pickup-map-link"
                                    href="https://www.openstreetmap.org/?mlat={{ $pickupLatitude }}&mlon={{ $pickupLongitude }}#map=16/{{ $pickupLatitude }}/{{ $pickupLongitude }}"
                                    target="_blank" rel="noopener noreferrer" @if ($pickupLatitude === null || $pickupLongitude === null) hidden @endif>{{ __('View pickup pin') }}</a>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-body-secondary">Drop-off address</div>
                                <div class="text-pre-line" id="ticket-dropoff-address">{{ $dropoffAddress ?: ($pickupAddress ? 'Same as pickup address' : '—') }}</div>
                                <a id="ticket-dropoff-map-link"
                                    href="https://www.openstreetmap.org/?mlat={{ $dropoffLatitude }}&mlon={{ $dropoffLongitude }}#map=16/{{ $dropoffLatitude }}/{{ $dropoffLongitude }}"
                                    target="_blank" rel="noopener noreferrer" @if ($dropoffLatitude === null || $dropoffLongitude === null) hidden @endif>{{ __('View drop-off pin') }}</a>
                            </div>
                        </div>
                        @endif
                    </div>
                @endif

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold">Repair steps and SOP log</span>
                        @unless (auth()->user()->hasRole('customer'))
                            @if ($ticket->status !== 'closed')
                                <button class="btn btn-sm btn-primary text-nowrap" type="button"
                                    data-bs-toggle="modal" data-bs-target="#ticket-step-modal">
                                    <span class="bi bi-plus me-1" aria-hidden="true"></span>Add repair step
                                </button>
                            @endif
                        @endunless
                    </div>
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
                    <div class="list-group list-group-flush" data-ticket-billing-documents aria-live="polite">
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
        @if (! $ticket->customer_id)
        <div class="modal fade" id="ticket-create-customer-modal" tabindex="-1"
            aria-labelledby="ticket-create-customer-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('tickets.customer.store', $ticket) }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="ticket-create-customer-title">Create customer record</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="ticket-customer-name">Customer name</label>
                                    <input class="form-control" id="ticket-customer-name" name="name" maxlength="255" required
                                        value="{{ old('name', $ticket->face_to_face_name) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ticket-customer-nickname">Nickname <span class="text-body-secondary">(optional)</span></label>
                                    <input class="form-control" id="ticket-customer-nickname" name="nickname" maxlength="255"
                                        value="{{ old('nickname', $ticket->face_to_face_nickname) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ticket-customer-phone">Contact number</label>
                                    <x-phone-input id="ticket-customer-phone" name="phone"
                                        :value="old('phone', $ticket->face_to_face_phone)" maxlength="64" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ticket-customer-email">Email</label>
                                    <input class="form-control" id="ticket-customer-email" name="email" type="email" maxlength="191"
                                        value="{{ old('email', $ticket->face_to_face_email) }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="ticket-customer-company">Company <span class="text-body-secondary">(optional)</span></label>
                                    <input class="form-control" id="ticket-customer-company" name="company" maxlength="255"
                                        value="{{ old('company') }}">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-end">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Create and link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
        <div class="modal fade" id="ticket-workflow-modal" tabindex="-1" aria-labelledby="ticket-workflow-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}"
                        data-ticket-ajax-form data-offline-draft="ticket-workflow">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="ticket-workflow-title">
                                {{ $ticket->status === 'closed' ? 'Update closed ticket status' : 'Edit ticket details' }}
                            </h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" role="alert" data-ticket-form-error></div>
                            <div class="row g-3">
                                @if ($ticket->status === 'closed')
                                    <div class="col-12">
                                        <div class="alert alert-info mb-0" role="status">
                                            Ticket details are locked. If the same issue returns under warranty, change the status to In repair.
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-status">Status</label>
                                    <select class="form-select" id="workflow-status" name="status" required>
                                        @if ($ticket->status === 'closed')
                                            <option value="closed" selected>Closed</option>
                                            <option value="in_repair">In repair</option>
                                        @else
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @if ($ticket->status !== 'closed')
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
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-pickup-address">Pickup address</label>
                                    <textarea class="form-control" id="workflow-pickup-address" name="pickup_address"
                                        rows="2" maxlength="1000" data-ticket-location-address="pickup">{{ $ticket->pickup_address ?? $ticket->booking?->pickup_address }}</textarea>
                                    <input type="hidden" name="pickup_latitude" value="{{ $ticket->pickup_latitude ?? $ticket->booking?->pickup_latitude }}"
                                        data-ticket-location-latitude="pickup">
                                    <input type="hidden" name="pickup_longitude" value="{{ $ticket->pickup_longitude ?? $ticket->booking?->pickup_longitude }}"
                                        data-ticket-location-longitude="pickup">
                                    <div class="small text-body-secondary mt-1" data-ticket-location-preview="pickup">{{ ($ticket->pickup_address ?? $ticket->booking?->pickup_address) ?: 'No address selected' }}</div>
                                    <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-ticket-location-open="pickup">Choose pickup on map</button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="workflow-dropoff-address">Drop-off address</label>
                                    <textarea class="form-control" id="workflow-dropoff-address" name="dropoff_address"
                                        rows="2" maxlength="1000" data-ticket-location-address="dropoff">{{ $ticket->dropoff_address ?? $ticket->booking?->dropoff_address }}</textarea>
                                    <input type="hidden" name="dropoff_latitude" value="{{ $ticket->dropoff_latitude ?? $ticket->booking?->dropoff_latitude }}"
                                        data-ticket-location-latitude="dropoff">
                                    <input type="hidden" name="dropoff_longitude" value="{{ $ticket->dropoff_longitude ?? $ticket->booking?->dropoff_longitude }}"
                                        data-ticket-location-longitude="dropoff">
                                    <div class="small text-body-secondary mt-1" data-ticket-location-preview="dropoff">{{ ($ticket->dropoff_address ?? $ticket->booking?->dropoff_address) ?: 'No address selected' }}</div>
                                    <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-ticket-location-open="dropoff">Choose drop-off on map</button>
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
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer justify-content-end">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">
                                {{ $ticket->status === 'closed' ? 'Update status' : 'Save workflow' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($ticket->status !== 'closed')
        <div class="modal fade" id="ticket-step-modal" tabindex="-1" aria-labelledby="ticket-step-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form id="ticket-step-form" class="modal-content" method="POST"
                    action="{{ route('tickets.steps.store', $ticket) }}" enctype="multipart/form-data"
                    data-ticket-ajax-form data-offline-draft="ticket-step">
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
                    <div class="modal-footer justify-content-end">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Add repair step</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @include('tickets.partials.location-picker')

        <div class="modal fade" id="ticket-step-photo-caption-modal" tabindex="-1"
            aria-labelledby="ticket-step-photo-caption-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5" id="ticket-step-photo-caption-title">Add photo caption</h2>
                            <div class="small text-body-secondary" data-ticket-photo-caption-filename></div>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <img class="ticket-step-photo-caption-modal-image mb-3"
                            data-ticket-photo-caption-image alt="">
                        <label class="form-label" for="ticket-step-photo-caption-input">Caption</label>
                        <input class="form-control" id="ticket-step-photo-caption-input"
                            data-ticket-photo-caption-input maxlength="255" autocomplete="off"
                            aria-describedby="ticket-step-photo-caption-error">
                        <div class="invalid-feedback" id="ticket-step-photo-caption-error">
                            Enter a caption for this photo.
                        </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="button" data-ticket-photo-caption-save>Save caption</button>
                    </div>
                </div>
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
            const photoCaptionModal = document.getElementById('ticket-step-photo-caption-modal');
            const photoCaptionInput = photoCaptionModal?.querySelector('[data-ticket-photo-caption-input]');
            const photoCaptionImage = photoCaptionModal?.querySelector('[data-ticket-photo-caption-image]');
            const photoCaptionFilename = photoCaptionModal?.querySelector('[data-ticket-photo-caption-filename]');
            let photoCaptionEditorState = null;
            const openPhotoCaptionEditor = async (form, index) => {
                if (!photoCaptionModal || !photoCaptionInput || photoCaptionEditorState) return;

                const input = form.querySelector('input[type="file"][name="photos[]"]');
                const file = input?.files?.[index];
                const card = form.querySelectorAll('.ticket-step-photo-preview-card')[index];
                const uploadModal = form.closest('.modal');
                if (!(input instanceof HTMLInputElement) || !file || !card || !uploadModal) return;

                photoCaptionEditorState = { form, input, index, uploadModal };
                const captions = stepPhotoCaptions.get(input) || [];
                const previewImage = card.querySelector('img');
                if (photoCaptionImage) {
                    photoCaptionImage.src = previewImage?.src || '';
                    photoCaptionImage.alt = `Preview of ${file.name}`;
                }
                if (photoCaptionFilename) photoCaptionFilename.textContent = file.name;
                photoCaptionInput.value = captions[index] || '';
                photoCaptionInput.classList.remove('is-invalid');

                await hideModal(uploadModal);
                bootstrap.Modal.getOrCreateInstance(photoCaptionModal).show();
            };
            const requestPhotoCaptionEditor = (form, index) => {
                openPhotoCaptionEditor(form, index).catch((error) => {
                    photoCaptionEditorState = null;
                    console.error('Could not open the repair photo caption editor.', error);
                    showToast('Could not open the caption editor. Please try again.', 'danger');
                });
            };
            photoCaptionModal?.addEventListener('shown.bs.modal', () => photoCaptionInput?.focus());
            photoCaptionModal?.addEventListener('hidden.bs.modal', () => {
                const state = photoCaptionEditorState;
                photoCaptionEditorState = null;
                if (state?.form.isConnected && state.uploadModal.isConnected) {
                    bootstrap.Modal.getOrCreateInstance(state.uploadModal).show();
                }
            });
            photoCaptionInput?.addEventListener('input', () => photoCaptionInput.classList.remove('is-invalid'));
            photoCaptionModal?.querySelector('[data-ticket-photo-caption-save]')?.addEventListener('click', () => {
                const state = photoCaptionEditorState;
                const caption = photoCaptionInput?.value.trim();
                if (!state || !photoCaptionInput || !caption) {
                    photoCaptionInput?.classList.add('is-invalid');
                    photoCaptionInput?.focus();
                    return;
                }

                const captions = [...(stepPhotoCaptions.get(state.input) || [])];
                captions[state.index] = caption;
                setStepPhotoFiles(state.form, Array.from(state.input.files || []), captions);
                const errorBox = state.form.querySelector('[data-ticket-form-error]');
                if (errorBox) {
                    errorBox.textContent = '';
                    errorBox.classList.add('d-none');
                }
                bootstrap.Modal.getInstance(photoCaptionModal)?.hide();
            });

            initializeLightbox();
            initializeStepCarousels();

            const clearStepPhotoPreview = (form) => {
                const preview = form.querySelector('[data-ticket-step-photo-preview]');
                if (!preview) return;

                const releaseImageUrl = (image) => {
                    const objectUrl = image.dataset.objectUrl;
                    if (!objectUrl) return;

                    delete image.dataset.objectUrl;
                    const revoke = () => URL.revokeObjectURL(objectUrl);
                    if (image.complete) {
                        revoke();
                    } else {
                        image.addEventListener('load', revoke, { once: true });
                        image.addEventListener('error', revoke, { once: true });
                    }
                };
                preview.querySelectorAll('img[data-object-url]').forEach((image) => {
                    releaseImageUrl(image);
                });
                preview.replaceChildren();
                preview.classList.add('d-none');
            };
            const stepPhotoCaptions = new WeakMap();
            const updateStepPhotoPreview = (input) => {
                const form = input.closest('form[data-ticket-step-photo-form]');
                if (!form) return;

                clearStepPhotoPreview(form);
                const files = Array.from(input.files || []);
                const preview = form.querySelector('[data-ticket-step-photo-preview]');
                if (files.length === 0 || !preview) return;
                const captions = stepPhotoCaptions.get(input) || files.map(() => '');
                stepPhotoCaptions.set(input, captions);

                files.forEach((file, index) => {
                    const card = document.createElement('div');
                    card.className = 'ticket-step-photo-preview-card';

                    const caption = captions[index] || '';
                    const imageButton = document.createElement('button');
                    imageButton.className = 'ticket-step-photo-image-button';
                    imageButton.type = 'button';
                    imageButton.dataset.ticketStepPhotoCaptionEdit = '';
                    imageButton.dataset.index = String(index);
                    imageButton.setAttribute('aria-label', `${caption ? 'Edit' : 'Add'} caption for ${file.name}`);
                    const image = document.createElement('img');
                    image.className = 'ticket-step-photo-upload-preview';
                    image.alt = `Preview of ${file.name}`;
                    image.dataset.objectUrl = URL.createObjectURL(file);
                    image.src = image.dataset.objectUrl;
                    imageButton.append(image);

                    const details = document.createElement('div');
                    details.className = 'ticket-step-photo-preview-details';
                    const fileName = document.createElement('span');
                    fileName.className = 'small text-truncate';
                    fileName.textContent = file.name;
                    const removeButton = document.createElement('button');
                    removeButton.className = 'btn btn-sm btn-outline-secondary';
                    removeButton.type = 'button';
                    removeButton.dataset.ticketStepPhotoRemove = '';
                    removeButton.dataset.index = String(index);
                    removeButton.setAttribute('aria-label', `Remove ${file.name}`);
                    const removeIcon = document.createElement('span');
                    removeIcon.className = 'bi bi-x-lg';
                    removeIcon.setAttribute('aria-hidden', 'true');
                    removeButton.append(removeIcon);

                    const captionButton = document.createElement('button');
                    captionButton.className = 'btn btn-sm btn-outline-secondary ticket-step-photo-caption-button';
                    captionButton.type = 'button';
                    captionButton.dataset.ticketStepPhotoCaptionEdit = '';
                    captionButton.dataset.index = String(index);
                    captionButton.textContent = caption || 'Add caption';
                    captionButton.setAttribute('aria-label', `${caption ? 'Edit' : 'Add'} caption for ${file.name}`);

                    const captionField = document.createElement('input');
                    captionField.type = 'hidden';
                    captionField.name = 'captions[]';
                    captionField.value = caption;

                    details.append(fileName, removeButton);
                    card.append(imageButton, details, captionButton, captionField);
                    preview.append(card);
                });
                preview.classList.remove('d-none');
            };
            document.addEventListener('offline-draft:restored', (event) => {
                const form = event.target instanceof HTMLFormElement ? event.target : null;
                if (!form?.matches('[data-ticket-step-photo-form]')) return;

                const input = form.querySelector('input[type="file"][name="photos[]"]');
                if (!(input instanceof HTMLInputElement)) return;
                stepPhotoCaptions.set(input, Array.from(
                    form.querySelectorAll('input[name="captions[]"]'),
                    (caption) => caption.value,
                ));
                selectedStepPhotos.set(input, Array.from(input.files || []));
                updateStepPhotoPreview(input);
            });
            const stepPhotoExtensions = {
                'image/jpeg': 'jpg',
                'image/png': 'png',
                'image/webp': 'webp',
            };
            const getStepPhotoErrorBox = (form) => {
                let errorBox = form.querySelector('[data-ticket-form-error]');
                if (!errorBox) {
                    errorBox = document.createElement('div');
                    errorBox.className = 'alert alert-danger d-none';
                    errorBox.setAttribute('role', 'alert');
                    errorBox.dataset.ticketFormError = '';
                    (form.querySelector('.modal-body') || form).prepend(errorBox);
                }

                return errorBox;
            };
            const showStepPhotoError = (form, message) => {
                const errorBox = getStepPhotoErrorBox(form);
                errorBox.textContent = message;
                errorBox.classList.remove('d-none');
            };
            const maxStepPhotos = 8;
            const selectedStepPhotos = new WeakMap();
            const programmaticStepPhotoChanges = new WeakSet();
            const getStepPhotoKey = (file) => `${file.name}\0${file.size}\0${file.lastModified}`;
            const setStepPhotoFiles = (form, files, captions = null) => {
                const input = form.querySelector('input[type="file"][name="photos[]"]');
                if (!(input instanceof HTMLInputElement)) return;
                if (files.length > maxStepPhotos) {
                    showStepPhotoError(form, `Choose up to ${maxStepPhotos} photos per upload.`);
                    return;
                }
                if (files.some((file) => !stepPhotoExtensions[file.type])) {
                    showStepPhotoError(form, 'Only JPEG, PNG, and WebP images can be added.');
                    return;
                }

                try {
                    const transfer = new DataTransfer();
                    files.forEach((file) => transfer.items.add(file));
                    input.files = transfer.files;
                } catch (error) {
                    console.error('Could not attach the repair-step photos.', error);
                    showStepPhotoError(form, 'Could not attach the photos. Choose files from your device instead.');
                    return;
                }

                const currentCaptions = captions || stepPhotoCaptions.get(input) || [];
                stepPhotoCaptions.set(input, files.map((_, index) => currentCaptions[index] || ''));
                programmaticStepPhotoChanges.add(input);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            document.addEventListener('change', (event) => {
                const input = event.target instanceof HTMLInputElement ? event.target : null;
                if (!input?.matches('input[type="file"][name="photos[]"]')) return;
                const form = input.closest('form[data-ticket-step-photo-form]');
                if (!form) return;

                const currentFiles = Array.from(input.files || []);
                if (programmaticStepPhotoChanges.has(input)) {
                    programmaticStepPhotoChanges.delete(input);
                    selectedStepPhotos.set(input, currentFiles);
                } else {
                    const previousFiles = selectedStepPhotos.get(input) || [];
                    const previousCaptions = stepPhotoCaptions.get(input) || [];
                    const mergedFiles = [...previousFiles];
                    const mergedCaptions = previousFiles.map((_, index) => previousCaptions[index] || '');
                    currentFiles.forEach((file) => {
                        if (!mergedFiles.some((existing) => getStepPhotoKey(existing) === getStepPhotoKey(file))) {
                            mergedFiles.push(file);
                            mergedCaptions.push('');
                        }
                    });
                    if (mergedFiles.length > maxStepPhotos) {
                        setStepPhotoFiles(form, previousFiles, previousCaptions);
                        showStepPhotoError(form, `Choose up to ${maxStepPhotos} photos per upload.`);
                        return;
                    }
                    const filesChanged = mergedFiles.length !== currentFiles.length
                        || mergedFiles.some((file, index) => getStepPhotoKey(file) !== getStepPhotoKey(currentFiles[index]));
                    if (previousFiles.length && filesChanged) {
                        setStepPhotoFiles(form, mergedFiles, mergedCaptions);
                        return;
                    }
                    selectedStepPhotos.set(input, mergedFiles);
                    stepPhotoCaptions.set(input, mergedCaptions);
                }

                const errorBox = form?.querySelector('[data-ticket-form-error]');
                if (currentFiles.length && errorBox) {
                    errorBox.textContent = '';
                    errorBox.classList.add('d-none');
                }
                updateStepPhotoPreview(input);
            });

            document.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const captionEditButton = target?.closest('[data-ticket-step-photo-caption-edit]');
                if (captionEditButton) {
                    const form = captionEditButton.closest('form[data-ticket-step-photo-form]');
                    const index = Number(captionEditButton.dataset.index);
                    if (form && Number.isInteger(index)) requestPhotoCaptionEditor(form, index);
                    return;
                }

                const removeButton = target?.closest('[data-ticket-step-photo-remove]');
                if (removeButton) {
                    const form = removeButton.closest('form[data-ticket-step-photo-form]');
                    const input = form?.querySelector('input[type="file"][name="photos[]"]');
                    if (input instanceof HTMLInputElement) {
                        const files = Array.from(input.files || []);
                        const index = Number(removeButton.dataset.index);
                        const captions = [...(stepPhotoCaptions.get(input) || [])];
                        files.splice(index, 1);
                        captions.splice(index, 1);
                        setStepPhotoFiles(form, files, captions);
                    }
                    return;
                }

                const dropzone = target?.closest('[data-ticket-step-photo-dropzone]');
                if (!dropzone || target?.closest('button, label, textarea, input')) return;
                dropzone.querySelector('[data-ticket-step-photo-paste]')?.focus();
            });

            document.addEventListener('dragover', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const dropzone = target?.closest('[data-ticket-step-photo-dropzone]');
                if (!dropzone) return;

                event.preventDefault();
                dropzone.classList.add('is-dragging');
            });
            document.addEventListener('dragleave', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const dropzone = target?.closest('[data-ticket-step-photo-dropzone]');
                if (!dropzone || (event.relatedTarget instanceof Node && dropzone.contains(event.relatedTarget))) return;
                dropzone.classList.remove('is-dragging');
            });
            document.addEventListener('drop', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const dropzone = target?.closest('[data-ticket-step-photo-dropzone]');
                if (!dropzone) return;

                event.preventDefault();
                dropzone.classList.remove('is-dragging');
                const files = Array.from(event.dataTransfer?.files || []);
                const form = dropzone.closest('form[data-ticket-step-photo-form]');
                if (!form || files.length === 0) return;
                const input = form.querySelector('input[type="file"][name="photos[]"]');
                if (!(input instanceof HTMLInputElement)) return;
                const currentFiles = Array.from(input.files || []);
                const captions = [...(stepPhotoCaptions.get(input) || []), ...files.map(() => '')];
                setStepPhotoFiles(form, [...currentFiles, ...files], captions);
            });

            document.addEventListener('paste', (event) => {
                const pasteTarget = event.target instanceof Element ? event.target : null;
                const pasteField = Boolean(pasteTarget?.closest('[data-ticket-step-photo-paste]'));
                const form = pasteTarget?.closest('form[data-ticket-step-photo-form]');
                if (!form) return;

                const clipboardItems = Array.from(event.clipboardData?.items || []);
                const clipboardFiles = Array.from(event.clipboardData?.files || []);
                const imageFiles = [
                    ...clipboardItems
                        .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
                        .map((item) => item.getAsFile())
                        .filter((file) => file instanceof File),
                    ...clipboardFiles.filter((file) => file.type.startsWith('image/')),
                ];

                const sourceFile = imageFiles.find((file) => stepPhotoExtensions[file.type]) || imageFiles[0];
                if (!sourceFile && !pasteField) return;

                event.preventDefault();
                const attachPastedPhoto = (file) => {
                    const extension = stepPhotoExtensions[file.type];
                    if (!extension) {
                        showStepPhotoError(form, 'Only JPEG, PNG, and WebP images can be pasted.');
                        return;
                    }

                    const input = form.querySelector('input[type="file"][name="photos[]"]');
                    if (!(input instanceof HTMLInputElement)) return;
                    const currentFiles = Array.from(input.files || []);
                    const captions = [...(stepPhotoCaptions.get(input) || []), ''];
                    setStepPhotoFiles(form, [...currentFiles, new File([file], `pasted-repair-photo.${extension}`, {
                            type: file.type,
                            lastModified: Date.now(),
                        })], captions);
                };

                const extension = sourceFile ? stepPhotoExtensions[sourceFile.type] : null;
                if (sourceFile && extension) {
                    attachPastedPhoto(sourceFile);
                    return;
                }

                if (sourceFile) {
                    showStepPhotoError(form, 'Only JPEG, PNG, and WebP images can be pasted.');
                    return;
                }

                if (typeof navigator.clipboard?.read !== 'function') {
                    showStepPhotoError(form, 'The clipboard does not contain an image. Copy a photo or choose a file.');
                    return;
                }

                navigator.clipboard.read().then(async (clipboardData) => {
                    for (const clipboardItem of clipboardData) {
                        const imageType = Object.keys(stepPhotoExtensions)
                            .find((type) => clipboardItem.types.includes(type));
                        if (imageType) {
                            const file = new File(
                                [await clipboardItem.getType(imageType)],
                                `pasted-repair-photo.${stepPhotoExtensions[imageType]}`,
                                { type: imageType, lastModified: Date.now() },
                            );
                            const input = form.querySelector('input[type="file"][name="photos[]"]');
                            if (!(input instanceof HTMLInputElement)) return;
                            const currentFiles = Array.from(input.files || []);
                            const captions = [...(stepPhotoCaptions.get(input) || []), ''];
                            setStepPhotoFiles(form, [...currentFiles, file], captions);
                            return;
                        }
                    }

                    showStepPhotoError(form, 'The clipboard does not contain a JPEG, PNG, or WebP image. Copy a photo or choose a file.');
                }).catch((error) => {
                    console.error('Could not read an image from the clipboard.', error);
                    showStepPhotoError(form, 'Could not read an image from the clipboard. Choose a file from your device instead.');
                });
            }, true);

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
                    if (form.matches('[data-ticket-step-photo-form]')) {
                        const captionInputs = Array.from(form.querySelectorAll('input[name="captions[]"]'));
                        const missingCaptionIndex = captionInputs.findIndex((caption) => !caption.value.trim());
                        if (missingCaptionIndex !== -1) {
                            showStepPhotoError(form, 'Add a caption to each photo before uploading.');
                            requestPhotoCaptionEditor(form, missingCaptionIndex);
                            return;
                        }
                    }
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
                        if (window.offlineDrafts) {
                            await window.offlineDrafts.clear(form).catch((error) => {
                                console.error('The online save succeeded, but its local draft could not be cleared.', error);
                                window.offlineDrafts.setStatus(
                                    form,
                                    'Saved online, but the local copy could not be cleared. Clear it manually if needed.',
                                    'warning',
                                    true,
                                );
                            });
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
                            clearStepPhotoPreview(form);
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
                            page.querySelector('#workflow-pickup-address').value = result.pickup_address || '';
                            page.querySelector('#workflow-dropoff-address').value = result.dropoff_address || '';
                            page.querySelector('[data-ticket-location-latitude="pickup"]').value = result.pickup_latitude ?? '';
                            page.querySelector('[data-ticket-location-longitude="pickup"]').value = result.pickup_longitude ?? '';
                            page.querySelector('[data-ticket-location-latitude="dropoff"]').value = result.dropoff_latitude ?? '';
                            page.querySelector('[data-ticket-location-longitude="dropoff"]').value = result.dropoff_longitude ?? '';
                            page.querySelector('[data-ticket-location-preview="pickup"]').textContent = result.pickup_address || 'No address selected';
                            page.querySelector('[data-ticket-location-preview="dropoff"]').textContent = result.dropoff_address || 'No address selected';
                            page.querySelector('#ticket-device-model').textContent = result.device_model || '';
                            page.querySelector('#ticket-serial-number').textContent = result.serial_number || '—';
                            const pickupAddress = page.querySelector('#ticket-pickup-address');
                            if (pickupAddress) pickupAddress.textContent = result.pickup_address || '—';
                            const dropoffAddress = page.querySelector('#ticket-dropoff-address');
                            if (dropoffAddress) {
                                dropoffAddress.textContent = result.dropoff_address || (result.pickup_address ? 'Same as pickup address' : '—');
                            }
                            const updateMapLink = (id, latitude, longitude) => {
                                const link = page.querySelector(`#${id}`);
                                if (!link) return;
                                const hasCoordinates = latitude !== null && latitude !== '' && longitude !== null && longitude !== '';
                                link.hidden = !hasCoordinates;
                                if (hasCoordinates) {
                                    link.href = `https://www.openstreetmap.org/?mlat=${latitude}&mlon=${longitude}#map=16/${latitude}/${longitude}`;
                                }
                            };
                            updateMapLink('ticket-pickup-map-link', result.pickup_latitude, result.pickup_longitude);
                            updateMapLink('ticket-dropoff-map-link', result.dropoff_latitude, result.dropoff_longitude);
                            const locationCard = page.querySelector('#ticket-location-card');
                            if (locationCard) {
                                locationCard.classList.toggle(
                                    'd-none',
                                    !result.pickup_address && !result.dropoff_address && !result.has_location_pin,
                                );
                            }
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
                        if (result.reload) window.location.reload();
                    } catch (error) {
                        const message = error.message || 'Could not save changes. Please try again.';
                        if (errorBox) {
                            errorBox.textContent = message;
                            errorBox.classList.remove('d-none');
                        } else {
                            showToast(message, 'danger');
                        }
                        if (form.matches('[data-offline-draft]')) {
                            window.offlineDrafts?.save(form).then(() => {
                                window.offlineDrafts.setStatus(
                                    form,
                                    navigator.onLine
                                        ? 'Could not sync this change. A local copy is still available in this browser.'
                                        : 'Connection lost. Your local draft is available; reconnect and submit it manually.',
                                    'warning',
                                    true,
                                );
                            }).catch((saveError) => {
                                console.error('Could not save a local copy after the ticket request failed.', saveError);
                                window.offlineDrafts?.setStatus(form, 'The ticket request failed and a local backup could not be saved.', 'danger');
                            });
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
