@extends('layouts.app')

@section('title', $ticket->ticket_number)

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Ticket" :title="$ticket->ticket_number" :back-url="route('tickets.index')"
            back-label="All tickets">
            @unless (auth()->user()->hasRole('customer'))
                <x-slot name="actions">
                    <a class="btn btn-outline-primary"
                        href="{{ route('billing.create', ['type' => 'quotation', 'repair_ticket_id' => $ticket->id]) }}"><span
                            class="fas fa-file-invoice-dollar me-2"></span>Create quotation</a>
                    <a class="btn btn-outline-primary" href="{{ route('customers.show', $ticket->customer) }}"><span
                            class="fas fa-user me-2"></span>Customer profile</a>
                </x-slot>
            @endunless
        </x-page-header>
        <div class="mb-3"><x-status-badge :status="$ticket->status" :label="$statuses[$ticket->status] ?? ucfirst($ticket->status)" /></div>
        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger">@foreach ($errors->all() as $error)
            <div>{{ $error }}</div>@endforeach
        </div>@endif
        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Device and reported issue</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Customer</div><a
                                    href="{{ route('customers.show', $ticket->customer) }}">{{ $ticket->customer->name }}</a>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Device</div>{{ $ticket->device_type ?: 'Device' }}
                                {{ $ticket->device_model }}
                            </div>
                            <div class="col-md-4">
                                <div class="small text-body-secondary">Serial number</div>
                                {{ $ticket->serial_number ?: '—' }}
                            </div>
                            <div class="col-md-6">
                                <div class="small text-body-secondary">Project</div>@if ($ticket->project)<a
                                href="{{ route('projects.show', $ticket->project) }}">{{ $ticket->project->name }}</a>@else—@endif
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
                    <div class="card-body text-pre-line">
                        {{ $ticket->diagnosis ?: 'No diagnosis or service notes recorded.' }}
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Ticket photos</div>
                    @unless (auth()->user()->hasRole('customer'))
                        <form class="card-body border-bottom" method="POST"
                            action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data">@csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label" for="ticket-photos">Photos</label>
                                    <x-image-upload-input id="ticket-photos" name="photos[]" :multiple="true"
                                        :required="true" />
                                </div>
                                <div class="col-md-5 h-100">
                                    <label class="form-label" for="ticket-photo-caption">Caption</label>
                                    <input class="form-control" id="ticket-photo-caption" name="caption" maxlength="255"
                                        placeholder="Intake condition or customer-provided image">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-outline-primary">Upload</button>
                                </div>
                            </div>
                            <div class="form-text">Up to 8 JPG, PNG, or WebP images, 10 MB each.</div>
                        </form>
                    @endunless
                    <div class="card-body">
                        @include('attachments.gallery', ['attachments' => $ticket->attachments, 'emptyMessage' => 'No ticket photos yet.'])
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Repair steps and SOP log</div>
                    @unless (auth()->user()->hasRole('customer'))
                        <form class="card-body border-bottom" method="POST" action="{{ route('tickets.steps.store', $ticket) }}"
                            enctype="multipart/form-data">@csrf
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label" for="step-title">Step performed</label><input
                                        class="form-control" id="step-title" name="title" maxlength="180"
                                        placeholder="Example: Inspect charging port" required></div>
                                <div class="col-md-6"><label class="form-label" for="step-status">Ticket status after
                                        step</label><select class="form-select" id="step-status" name="status"
                                        required>@foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select></div>
                                <div class="col-12"><label class="form-label" for="step-notes">Procedure and
                                        findings</label><textarea class="form-control" id="step-notes" name="notes" rows="3"
                                        maxlength="5000"
                                        placeholder="Record checks performed, result, parts used, and customer approval. Keep sensitive customer data out of photos."></textarea>
                                </div>
                                <div class="col-md-8"><label class="form-label" for="step-photos">Step photos <span
                                            class="text-body-secondary">(optional)</span></label><x-image-upload-input
                                        id="step-photos" name="photos[]" :multiple="true" :required="false" />
                                    <div class="form-text">Photos are attached to this specific repair step.</div>
                                </div>
                                <div class="col-md-4"><label class="form-label" for="step-caption">Photo caption</label><input
                                        class="form-control" id="step-caption" name="caption" maxlength="255"></div>
                            </div>
                            <div class="text-end mt-3"><button class="btn btn-primary"><span class="fas fa-plus me-2"></span>Add
                                    repair step</button></div>
                        </form>
                    @endunless
                    <div class="list-group list-group-flush">
                        @forelse ($ticket->updates as $update)
                            <article class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <h3 class="h6 mb-1">{{ $update->title }}</h3><x-status-badge :status="$update->status"
                                            :label="$statuses[$update->status] ?? ucfirst($update->status)" />
                                    </div>
                                    <div class="small text-body-secondary text-end">
                                        {{ $update->created_at->format('M j, Y g:i A') }}<br>{{ $update->user?->name ?? 'System' }}
                                    </div>
                                </div>@if ($update->notes)
                                <p class="text-pre-line mt-2 mb-0">{{ $update->notes }}</p>@endif
                                @if ($update->attachments->isNotEmpty())
                                    <div class="mt-3">
                                        @include('attachments.gallery', ['attachments' => $update->attachments, 'emptyMessage' => ''])
                                </div>@endif
                            </article>
                        @empty
                            <div class="list-group-item text-body-secondary">No repair steps recorded.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-header fw-semibold">Billing documents</div>
                    <div class="list-group list-group-flush">@forelse ($ticket->billingDocuments as $document)<a
                        class="list-group-item list-group-item-action d-flex justify-content-between"
                        href="{{ route('billing.documents.show', $document) }}"><span>{{ $document->number }}<small
                                class="d-block text-body-secondary">{{ ucfirst($document->type) }} · <x-status-badge
                                    :status="$document->status"
                                    :label="ucfirst($document->status)" /></small></span><span>RM
                    {{ number_format($document->total, 2) }}</span></a>@empty<div
                                    class="list-group-item text-body-secondary">No quotations or invoices linked yet.</div>
                                @endforelse</div>
                </div>
            </div>
            @unless (auth()->user()->hasRole('customer'))
                    <div class="col-xl-5">
                        <form class="card" method="POST" action="{{ route('tickets.update', $ticket) }}">@csrf @method('PUT')
                            <div class="card-header fw-semibold">Ticket workflow</div>
                            <div class="card-body">
                                <div class="mb-3"><label class="form-label" for="status">Status</label><select class="form-select"
                                        id="status" name="status" required>@foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}
                                        </option>@endforeach
                                    </select></div>
                                <div class="mb-3"><label class="form-label" for="priority">Priority</label><select
                                        class="form-select" id="priority" name="priority"
                                        required>@foreach ($priorities as $value => $label)
                                            <option value="{{ $value }}" @selected(old('priority', $ticket->priority) === $value)>
                                                {{ $label }}
                                        </option>@endforeach
                                    </select></div>
                                @role('admin')
                                <div class="mb-3"><label class="form-label" for="assignees">Assign people</label><input
                                        type="hidden" name="assignees_present" value="1"><select class="form-select js-user-search"
                                        id="assignees" name="assignees[]" multiple
                                        data-placeholder="Search and select people">@foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('assignees', $ticket->assignees->pluck('id')->all())), true))>{{ $user->name }}
                                        </option>@endforeach
                                    </select></div>
                                @endrole
                                <div class="mb-3"><label class="form-label" for="due_at">Target date</label><input
                                        class="form-control" id="due_at" name="due_at" type="date"
                                        value="{{ old('due_at', $ticket->due_at?->format('Y-m-d')) }}"></div>
                                <div><label class="form-label" for="diagnosis">Diagnosis / work performed</label><textarea
                                        class="form-control" id="diagnosis" name="diagnosis" rows="6"
                                        maxlength="5000">{{ old('diagnosis', $ticket->diagnosis) }}</textarea></div>
                            </div>
                            <div class="card-footer text-end"><button class="btn btn-primary">Save ticket</button></div>
                        </form>
                    </div>
                </div>
            @endunless
    </div>
    </div>
@endsection