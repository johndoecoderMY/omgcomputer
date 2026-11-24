@extends('layouts.app')

@section('title', 'New Repair Ticket')

@push('styles')
    <style>
        [contenteditable][data-placeholder]:empty::before {
            color: var(--falcon-gray-600);
            content: attr(data-placeholder);
            pointer-events: none;
        }
    </style>
@endpush

@section('content')
@php
    $issueDescriptionHtml = app(\App\Support\RepairTicketIssueSanitizer::class)
        ->sanitize((string) old('issue_description', $ticket->issue_description ?? ''));
@endphp
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" :title="$booking ? 'Create ticket from booking' : 'New repair ticket'"
        :back-url="$booking ? route('bookings.show', $booking) : route('tickets.index')"
        :back-label="$booking ? 'Back to booking' : 'Back to tickets'" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="card p-2" method="POST" action="{{ route('tickets.store') }}" data-offline-draft="ticket-create" data-ticket-create-ajax>
        @csrf
        <div class="alert alert-danger d-none mb-0" role="alert" data-ticket-create-error></div>
        @if ($booking)<input type="hidden" name="booking_id" value="{{ $booking->id }}">@endif
        <div class="card-body row g-3">
        <div class="col-md-6">
            <div class="alert alert-info d-none mb-0" data-ticket-face-to-face-message>
                <span class="bi bi-shop me-1" aria-hidden="true"></span>
                Face-to-face / walk-in customer: pickup and drop-off are handled at the shop.
            </div>
        </div>
        <div class="col-md-6" data-ticket-face-to-face-address>
            <label class="form-label" for="customer_id">Customer</label>
            @if ($booking && ! $booking->customer)
                <div class="form-control h-auto">
                    {{ $booking->customer_name }} · {{ $booking->customer_email }}
                </div>
            @else
                <select class="form-select" id="customer_id" name="customer_id" @if (! $booking) required @endif @disabled($booking)>
                    @if (! $booking)
                        <option value="">Select customer</option>
                        <option value="__new_customer__">Add new customer…</option>
                    @endif
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $ticket->customer_id) === (string) $customer->id)>
                            {{ $customer->name }}{{ $customer->company ? ' · '.$customer->company : '' }}
                        </option>
                    @endforeach
                </select>
                @if ($booking)<input type="hidden" name="customer_id" value="{{ $ticket->customer_id }}">@endif
                @unless ($booking)
                    <input type="hidden" name="face_to_face" value="0">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="face_to_face" name="face_to_face" value="1"
                            @checked(old('face_to_face') == '1')>
                        <label class="form-check-label" for="face_to_face">
                            Face-to-face / walk-in customer <span class="text-body-secondary">(optional)</span>
                        </label>
                        <div class="form-text">Create the ticket without linking it to a customer account.</div>
                    </div>
                    <div class="row g-2 mt-2 d-none" data-ticket-face-to-face-contact>
                        <div class="col-md-4">
                            <label class="form-label" for="face_to_face_name">Customer name <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="face_to_face_name" name="face_to_face_name" maxlength="255"
                                value="{{ old('face_to_face_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="face_to_face_nickname">Nickname <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="face_to_face_nickname" name="face_to_face_nickname" maxlength="255"
                                value="{{ old('face_to_face_nickname') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="face_to_face_phone">Contact number <span class="text-body-secondary">(optional)</span></label>
                            <x-phone-input id="face_to_face_phone" name="face_to_face_phone"
                                :value="old('face_to_face_phone')" maxlength="64" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="face_to_face_email">Email <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="face_to_face_email" name="face_to_face_email" type="email" maxlength="255"
                                value="{{ old('face_to_face_email') }}">
                        </div>
                        <div class="col-12 form-text">Provide at least a contact number or email.</div>
                        @error('face_to_face_contact')
                            <div class="col-12 text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                @endunless
            @endif
        </div>
        <div class="col-md-4"><label class="form-label" for="device_type">Device type</label><select class="form-select" id="device_type" name="device_type"><option value="">Select type</option>@foreach (['Laptop', 'Desktop PC', 'Workstation', 'Other', 'Computer'] as $deviceType)<option value="{{ $deviceType }}" @selected(old('device_type', $ticket->device_type) === $deviceType)>{{ $deviceType }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="device_model">Make / model</label><input class="form-control" id="device_model" name="device_model" value="{{ old('device_model', $ticket->device_model) }}" maxlength="255"></div>
        <div class="col-md-4"><label class="form-label" for="serial_number">Serial number</label><input class="form-control" id="serial_number" name="serial_number" value="{{ old('serial_number') }}" maxlength="255"></div>
        <div class="col-md-12 d-none" data-ticket-face-to-face-message>
            <div class="alert alert-info mb-0">
                <span class="bi bi-shop me-1" aria-hidden="true"></span>
                Face-to-face / walk-in customer: pickup and drop-off are handled at the shop.
            </div>
        </div>
        <div class="col-md-6" data-ticket-face-to-face-address>
            <label class="form-label" for="pickup_address">Pickup address <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="pickup_address" name="pickup_address" rows="2" maxlength="1000"
                data-ticket-location-address="pickup">{{ old('pickup_address', $ticket->pickup_address) }}</textarea>
            <input type="hidden" name="pickup_latitude" value="{{ old('pickup_latitude', $ticket->pickup_latitude) }}"
                data-ticket-location-latitude="pickup">
            <input type="hidden" name="pickup_longitude" value="{{ old('pickup_longitude', $ticket->pickup_longitude) }}"
                data-ticket-location-longitude="pickup">
            <div class="booking-location-preview small text-body-secondary mt-1" data-ticket-location-preview="pickup">
                {{ old('pickup_address', $ticket->pickup_address) ?: 'No address selected' }}
            </div>
            <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-ticket-location-open="pickup">
                <span class="bi bi-geo-alt me-1" aria-hidden="true"></span>Choose pickup on map
            </button>
        </div>
        <div class="col-md-6" data-ticket-face-to-face-address>
            <label class="form-label" for="dropoff_address">Drop-off address <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="dropoff_address" name="dropoff_address" rows="2" maxlength="1000"
                data-ticket-location-address="dropoff">{{ old('dropoff_address', $ticket->dropoff_address) }}</textarea>
            <input type="hidden" name="dropoff_latitude" value="{{ old('dropoff_latitude', $ticket->dropoff_latitude) }}"
                data-ticket-location-latitude="dropoff">
            <input type="hidden" name="dropoff_longitude" value="{{ old('dropoff_longitude', $ticket->dropoff_longitude) }}"
                data-ticket-location-longitude="dropoff">
            <div class="booking-location-preview small text-body-secondary mt-1" data-ticket-location-preview="dropoff">
                {{ old('dropoff_address', $ticket->dropoff_address) ?: 'No address selected' }}
            </div>
            <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-ticket-location-open="dropoff">
                <span class="bi bi-geo-alt me-1" aria-hidden="true"></span>Choose drop-off on map
            </button>
            <div class="form-text">Leave blank if it is the same as the pickup address.</div>
        </div>
        @role('admin')
            <div class="col-md-6"><label class="form-label" for="assignees">Assign people</label><input type="hidden" name="assignees_present" value="1"><select class="form-select js-user-search" id="assignees" name="assignees[]" multiple data-placeholder="Search and select people">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('assignees', [])), true))>{{ $user->name }}</option>@endforeach</select></div>
        @endrole
        <div class="col-md-3"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority" required>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', $ticket->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="due_at">Target date</label><input class="form-control" id="due_at" name="due_at" type="date" value="{{ old('due_at') }}"></div>
        <div class="col-12">
            <label class="form-label" id="issue-description-label" for="issue_description_editor">Reported issue</label>
            <div class="repair-issue-editor border rounded-2 mb-2" data-repair-issue-editor hidden>
                <div class="d-flex flex-wrap gap-1 border-bottom p-2" role="toolbar" aria-label="Reported issue formatting">
                    <button class="btn btn-sm btn-light border" type="button" data-issue-command="bold" aria-label="Bold" title="Bold"><span class="bi bi-type-bold" aria-hidden="true"></span></button>
                    <button class="btn btn-sm btn-light border" type="button" data-issue-command="italic" aria-label="Italic" title="Italic"><span class="bi bi-type-italic" aria-hidden="true"></span></button>
                    <button class="btn btn-sm btn-light border" type="button" data-issue-command="underline" aria-label="Underline" title="Underline"><span class="bi bi-type-underline" aria-hidden="true"></span></button>
                    <span class="vr mx-1"></span>
                    <button class="btn btn-sm btn-light border" type="button" data-issue-command="insertUnorderedList" aria-label="Bulleted list" title="Bulleted list"><span class="bi bi-list-ul" aria-hidden="true"></span></button>
                    <button class="btn btn-sm btn-light border" type="button" data-issue-command="insertOrderedList" aria-label="Numbered list" title="Numbered list"><span class="bi bi-list-ol" aria-hidden="true"></span></button>
                    <button class="btn btn-sm btn-light border ms-auto" type="button" data-issue-command="removeFormat" aria-label="Clear formatting" title="Clear formatting"><span class="bi bi-eraser" aria-hidden="true"></span></button>
                </div>
                <div class="form-control border-0 rounded-0 p-3" id="issue_description_editor" contenteditable="true" role="textbox" aria-labelledby="issue-description-label" aria-multiline="true" aria-describedby="issue-description-help issue-description-count" aria-invalid="false" data-placeholder="Describe the issue and any error messages…" data-offline-draft-editor-for="issue_description" style="min-height: 10rem;">{!! $issueDescriptionHtml !!}</div>
            </div>
            <textarea class="form-control" id="issue_description" name="issue_description" rows="5" maxlength="20000" required>{{ old('issue_description', $ticket->issue_description) }}</textarea>
            <div class="d-flex justify-content-between gap-3 mt-1">
                <div class="form-text" id="issue-description-help">Describe the symptoms, when they started, and any error messages. Basic formatting and lists are supported.</div>
                <div class="form-text text-nowrap" id="issue-description-count" aria-live="polite">0 / 5000</div>
            </div>
            <div class="invalid-feedback" data-issue-description-error></div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">Create ticket</button>
    </div>

    @unless ($booking)
        <div class="modal fade" id="new-customer-modal" tabindex="-1" aria-labelledby="new-customer-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="new-customer-title">Add customer</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" id="new-customer-error" role="alert"></div>
                        <div class="mb-3">
                            <label class="form-label" for="new_customer_name">Customer name</label>
                            <input class="form-control" id="new_customer_name" maxlength="255"
                                data-offline-draft-field="new_customer_name">
                            <div class="invalid-feedback">Enter the customer's name.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="new_customer_email">Email <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="new_customer_email" type="email" maxlength="255"
                                data-offline-draft-field="new_customer_email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="new_customer_phone">Phone <span class="text-body-secondary">(optional)</span></label>
                            <x-phone-input id="new_customer_phone" maxlength="64"
                                data-offline-draft-field="new_customer_phone" />
                        </div>
                        <div>
                            <label class="form-label" for="new_customer_company">Company <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="new_customer_company" maxlength="255"
                                data-offline-draft-field="new_customer_company">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="button" id="save-new-customer">Use customer</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const customerSelect = document.getElementById('customer_id');
                const modalElement = document.getElementById('new-customer-modal');
                const customerName = document.getElementById('new_customer_name');
                const customerEmail = document.getElementById('new_customer_email');
                const customerPhone = document.getElementById('new_customer_phone');
                const customerCompany = document.getElementById('new_customer_company');
                const errorBox = document.getElementById('new-customer-error');
                const saveButton = document.getElementById('save-new-customer');
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
                let customerSaved = false;
                const faceToFace = document.getElementById('face_to_face');
                if (faceToFace) {
                    const syncFaceToFace = () => {
                        customerSelect.disabled = faceToFace.checked;
                        customerSelect.required = !faceToFace.checked;
                        if (faceToFace.checked) {
                            customerSelect.value = '';
                        }
                        document.querySelectorAll('[data-ticket-face-to-face-address]').forEach((addressField) => {
                            addressField.classList.toggle('d-none', faceToFace.checked);
                        });
                        document.querySelectorAll('[data-ticket-face-to-face-message]').forEach((message) => {
                            message.classList.toggle('d-none', !faceToFace.checked);
                        });
                        document.querySelectorAll('[data-ticket-face-to-face-contact]').forEach((contact) => {
                            contact.classList.toggle('d-none', !faceToFace.checked);
                        });
                        if (faceToFace.checked) {
                            ['pickup', 'dropoff'].forEach((location) => {
                                document.querySelector(`[data-ticket-location-address="${location}"]`)?.removeAttribute('name');
                                document.querySelector(`[data-ticket-location-latitude="${location}"]`)?.removeAttribute('name');
                                document.querySelector(`[data-ticket-location-longitude="${location}"]`)?.removeAttribute('name');
                            });
                        } else {
                            ['pickup', 'dropoff'].forEach((location) => {
                                document.querySelector(`[data-ticket-location-address="${location}"]`)?.setAttribute('name', `${location}_address`);
                                document.querySelector(`[data-ticket-location-latitude="${location}"]`)?.setAttribute('name', `${location}_latitude`);
                                document.querySelector(`[data-ticket-location-longitude="${location}"]`)?.setAttribute('name', `${location}_longitude`);
                            });
                        }
                    };
                    faceToFace.addEventListener('change', syncFaceToFace);
                    syncFaceToFace();
                }

                customerSelect.addEventListener('change', () => {
                    if (customerSelect.value === '__new_customer__') {
                        customerSelect.value = '';
                        modal.show();
                    }
                });
                saveButton.addEventListener('click', async () => {
                    errorBox.classList.add('d-none');
                    customerName.classList.remove('is-invalid');
                    if (!customerName.value.trim()) {
                        customerName.classList.add('is-invalid');
                        customerName.focus();
                        return;
                    }
                    if (!navigator.onLine) {
                        errorBox.textContent = 'You are offline. These customer details are saved with the ticket draft; reconnect to add the customer.';
                        errorBox.classList.remove('d-none');
                        const ticketForm = document.querySelector('form[data-offline-draft="ticket-create"]');
                        if (ticketForm) {
                            window.offlineDrafts?.save(ticketForm).then(() => {
                                window.offlineDrafts.setStatus(
                                    ticketForm,
                                    'Offline draft saved on this device. Reconnect to add the customer and submit the ticket.',
                                    'warning',
                                    true,
                                );
                            }).catch((error) => {
                                console.error('Could not save the offline ticket draft.', error);
                                window.offlineDrafts?.setStatus(
                                    ticketForm,
                                    'Could not save a local backup. Keep this page open while offline.',
                                    'danger',
                                );
                            });
                        }
                        return;
                    }
                    saveButton.disabled = true;
                    saveButton.textContent = 'Saving…';

                    try {
                        const response = await fetch(@json(route('tickets.customers.store')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                            },
                            body: JSON.stringify({
                                name: customerName.value.trim(),
                                email: customerEmail.value.trim() || null,
                                phone: window.getInternationalPhoneNumber?.(customerPhone) || customerPhone.value.trim() || null,
                                company: customerCompany.value.trim() || null,
                            }),
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            const errors = Object.values(result.errors || {}).flat();
                            throw new Error(errors.join(' ') || result.message || 'Customer could not be created.');
                        }

                        const option = new Option(result.text, result.id, true, true);
                        customerSelect.add(option, customerSelect.options.length - 1);
                        customerSelect.value = result.id;
                        customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        customerSaved = true;
                        modal.hide();
                        modalElement.querySelectorAll('input').forEach((input) => {
                            input.value = '';
                        });
                    } catch (error) {
                        errorBox.textContent = error.message || 'Could not save the customer. Please try again.';
                        errorBox.classList.remove('d-none');
                    } finally {
                        saveButton.disabled = false;
                        saveButton.textContent = 'Use customer';
                    }
                });
                modalElement.addEventListener('hidden.bs.modal', () => {
                    if (!customerSaved) {
                        customerSelect.value = '';
                    }
                    customerSaved = false;
                    errorBox.classList.add('d-none');
                });
            });
        </script>
    @endunless
    </form>
    @include('tickets.partials.location-picker')
</div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('form[action="{{ route('tickets.store') }}"]');
            const textarea = document.getElementById('issue_description');
            const editor = document.querySelector('[data-repair-issue-editor]');
            const editable = document.getElementById('issue_description_editor');
            const counter = document.getElementById('issue-description-count');
            const errorMessage = document.querySelector('[data-issue-description-error]');

            if (!form || !textarea || !editor || !editable || !counter || !errorMessage) {
                return;
            }

            const characterCount = () => [...editable.innerText].length;
            const updateCount = () => {
                const count = characterCount();
                counter.textContent = `${count} / 5000`;
                counter.classList.toggle('text-danger', count > 5000);
                errorMessage.textContent = count > 5000
                    ? 'The reported issue may not be greater than 5000 characters.'
                    : '';
                errorMessage.classList.toggle('d-block', count > 5000);
                editable.setAttribute('aria-invalid', count > 5000 ? 'true' : 'false');
            };

            editable.addEventListener('input', () => {
                textarea.value = editable.innerHTML;
                updateCount();
            });
            editable.addEventListener('paste', (event) => {
                event.preventDefault();
                document.execCommand('insertText', false, event.clipboardData?.getData('text/plain') || '');
            });
            editable.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    document.execCommand('insertParagraph');
                    event.preventDefault();
                }
            });

            editor.querySelectorAll('[data-issue-command]').forEach((button) => {
                button.addEventListener('mousedown', (event) => event.preventDefault());
                button.addEventListener('click', () => {
                    editable.focus();
                    document.execCommand(button.dataset.issueCommand, false);
                    updateCount();
                });
            });

            form.addEventListener('submit', (event) => {
                const hasText = editable.innerText.trim().length > 0;
                const isTooLong = characterCount() > 5000;

                if (!hasText || isTooLong) {
                    event.preventDefault();
                    errorMessage.textContent = hasText
                        ? 'The reported issue may not be greater than 5000 characters.'
                        : 'Please describe the reported issue.';
                    errorMessage.classList.add('d-block');
                    editable.setAttribute('aria-invalid', 'true');
                    editable.focus();
                    return;
                }

                textarea.value = editable.innerHTML;
            });

            const createError = form.querySelector('[data-ticket-create-error]');
            const submitButton = form.querySelector('[type="submit"]');
            form.addEventListener('submit', async (event) => {
                if (event.defaultPrevented) {
                    return;
                }

                event.preventDefault();
                if (form.dataset.ajaxSubmitting === 'true') {
                    return;
                }

                form.dataset.ajaxSubmitting = 'true';
                createError?.classList.add('d-none');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Creating ticket…';
                }

                try {
                    const response = await fetch(form.action, {
                        method: form.method,
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await response.json();

                    if (!response.ok) {
                        const validationMessages = Object.values(result.errors || {}).flat();
                        throw new Error(validationMessages.join(' ') || result.message || 'The ticket could not be created.');
                    }

                    if (window.offlineDrafts) {
                        await window.offlineDrafts.clear(form).catch((error) => {
                            console.error('Unable to clear the saved ticket draft.', error);
                        });
                    }
                    window.location.assign(result.redirect_url);
                } catch (error) {
                    if (createError) {
                        createError.textContent = error instanceof Error
                            ? error.message
                            : 'The ticket could not be created. Please try again.';
                        createError.classList.remove('d-none');
                    }
                } finally {
                    form.dataset.ajaxSubmitting = 'false';
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Create ticket';
                    }
                }
            });

            textarea.hidden = true;
            textarea.required = false;
            editor.hidden = false;
            updateCount();
        })();
    </script>
@endpush