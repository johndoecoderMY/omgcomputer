@extends('layouts.app')

@section('title', 'New Repair Ticket')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" :title="$booking ? 'Create ticket from booking' : 'New repair ticket'"
        :back-url="$booking ? route('bookings.show', $booking) : route('tickets.index')"
        :back-label="$booking ? 'Back to booking' : 'Back to tickets'" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="card p-2" method="POST" action="{{ route('tickets.store') }}">@csrf
        @if ($booking)<input type="hidden" name="booking_id" value="{{ $booking->id }}">@endif
        <div class="card-body row g-3">
        <div class="col-md-6">
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
            @endif
        </div>
        <div class="col-md-4"><label class="form-label" for="device_type">Device type</label><select class="form-select" id="device_type" name="device_type"><option value="">Select type</option>@foreach (['Laptop', 'Desktop PC', 'Workstation', 'Other', 'Computer'] as $deviceType)<option value="{{ $deviceType }}" @selected(old('device_type', $ticket->device_type) === $deviceType)>{{ $deviceType }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="device_model">Make / model</label><input class="form-control" id="device_model" name="device_model" value="{{ old('device_model', $ticket->device_model) }}" maxlength="255"></div>
        <div class="col-md-4"><label class="form-label" for="serial_number">Serial number</label><input class="form-control" id="serial_number" name="serial_number" value="{{ old('serial_number') }}" maxlength="255"></div>
        @role('admin')
            <div class="col-md-6"><label class="form-label" for="assignees">Assign people</label><input type="hidden" name="assignees_present" value="1"><select class="form-select js-user-search" id="assignees" name="assignees[]" multiple data-placeholder="Search and select people">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('assignees', [])), true))>{{ $user->name }}</option>@endforeach</select></div>
        @endrole
        <div class="col-md-3"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority" required>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', $ticket->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="due_at">Target date</label><input class="form-control" id="due_at" name="due_at" type="date" value="{{ old('due_at') }}"></div>
        <div class="col-12"><label class="form-label" for="issue_description">Reported issue</label><textarea class="form-control" id="issue_description" name="issue_description" rows="5" maxlength="5000" required>{{ old('issue_description', $ticket->issue_description) }}</textarea></div>
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
                            <input class="form-control" id="new_customer_name" maxlength="255">
                            <div class="invalid-feedback">Enter the customer's name.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="new_customer_email">Email <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="new_customer_email" type="email" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="new_customer_phone">Phone <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="new_customer_phone" maxlength="64">
                        </div>
                        <div>
                            <label class="form-label" for="new_customer_company">Company <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="new_customer_company" maxlength="255">
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
                                phone: customerPhone.value.trim() || null,
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
</div>
@endsection