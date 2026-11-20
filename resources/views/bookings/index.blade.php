@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
<style>
    .booking-filter-fab {
        display: none;
    }

    @media (max-width: 991.98px) {
        .booking-filter-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1030;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 .5rem 1.25rem rgba(23, 32, 51, .28);
        }
    }
</style>
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="{{ $customerView ? 'My bookings' : 'Bookings' }}">
      
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @php
        $bookingFilters = array_merge(
            request()->only(['status', 'service', 'customer_name', 'starts_from', 'starts_to']),
            is_array(request('filter')) ? request('filter') : [],
        );
        $activeBookingFilters = collect(['status', 'service', 'customer_name', 'starts_from', 'starts_to'])
            ->filter(fn (string $filter): bool => filled($bookingFilters[$filter] ?? null))
            ->count();
    @endphp
    <div class="card p-2">
        <div class="d-flex justify-content-end mb-2">
            <button class="booking-filter-toolbar-button btn btn-outline-primary d-none d-lg-inline-flex" type="button" data-bs-toggle="modal" data-bs-target="#booking-filters-modal">
                <span class="bi bi-filter me-2" aria-hidden="true"></span>Filters
                @if ($activeBookingFilters > 0)
                    <span class="badge text-bg-primary ms-1">{{ $activeBookingFilters }}</span>
                @endif
            </button>
        </div>
        <div class="table-responsive">
        <table class="table align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('bookings.datatable', ['filter' => $bookingFilters]) }}"
            data-columns="{{ json_encode([
                ['data' => 'starts_at', 'name' => 'starts_at'],
                ['data' => 'reference', 'name' => 'reference'],
                ['data' => 'customer_contact', 'orderable' => false, 'searchable' => false],
                ['data' => 'service', 'orderable' => false, 'searchable' => false],
                ['data' => 'request_notes', 'name' => 'request_notes'],
                ['data' => 'status_label', 'name' => 'status'],
                ...($customerView ? [] : [['data' => 'management', 'orderable' => false, 'searchable' => false]]),
            ]) }}">
            <thead><tr><th>When</th><th>Reference</th><th>Customer</th><th>Service</th><th>Request</th><th>Status</th>@unless ($customerView)<th>Manage</th>@endunless</tr></thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
    <div id="booking-update-feedback" class="alert alert-success d-none mt-3" role="status" aria-live="polite"></div>
    <button class="booking-filter-fab btn btn-primary rounded-pill px-4 py-3" type="button" data-bs-toggle="modal" data-bs-target="#booking-filters-modal">
        <span class="bi bi-filter me-2" aria-hidden="true"></span>Filters
        @if ($activeBookingFilters > 0)
        <span class="badge text-bg-light text-primary ms-1">{{ $activeBookingFilters }}</span>
        @endif
    </button>
    <div class="modal fade" id="booking-filters-modal" tabindex="-1" aria-labelledby="booking-filters-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('bookings.index') }}">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="booking-filters-title">Filter bookings</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="booking-filter-status">Status</label>
                        <select class="form-select" id="booking-filter-status" name="filter[status]">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(($bookingFilters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="booking-filter-service">Service type</label>
                        <select class="form-select" id="booking-filter-service" name="filter[service]">
                            <option value="">All services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service }}" @selected(($bookingFilters['service'] ?? '') === $service)>{{ $service }}</option>
                            @endforeach
                        </select>
                    </div>
                    @unless ($customerView)
                        <div class="mb-3">
                            <label class="form-label" for="booking-filter-customer-name">Customer name</label>
                            <input class="form-control" id="booking-filter-customer-name" name="filter[customer_name]" value="{{ $bookingFilters['customer_name'] ?? '' }}" placeholder="Search name">
                        </div>
                    @endunless
                    <div class="mb-3">
                        <label class="form-label" for="booking-filter-starts-from">From</label>
                        <input class="form-control" id="booking-filter-starts-from" name="filter[starts_from]" type="datetime-local" value="{{ $bookingFilters['starts_from'] ?? '' }}">
                    </div>
                    <div>
                        <label class="form-label" for="booking-filter-starts-to">To</label>
                        <input class="form-control" id="booking-filter-starts-to" name="filter[starts_to]" type="datetime-local" value="{{ $bookingFilters['starts_to'] ?? '' }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>
        </div>
    </div>
    @unless ($customerView)
        <div class="modal fade" id="booking-status-modal" tabindex="-1" aria-labelledby="booking-status-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="booking-status-form" method="POST" action="#">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5" id="booking-status-title">Update booking</h2>
                            <div class="small text-body-secondary" data-booking-modal-reference></div>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" role="alert" data-booking-update-error></div>
                        <div class="mb-3">
                            <label class="form-label" for="booking-status-value">Status</label>
                            <select class="form-select" id="booking-status-value" name="status" required>
                                @foreach ($editableStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (auth()->user()->hasRole('admin'))
                            <div>
                                <label class="form-label" for="booking-assigned-to">Assigned staff</label>
                                <select class="form-select" id="booking-assigned-to" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit" data-booking-update-submit>Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
</div>
@endsection

@unless ($customerView)
    @push('scripts')
        <script>
            (() => {
                const table = document.querySelector('table[data-datatable]');
                const modal = document.getElementById('booking-status-modal');
                const form = document.getElementById('booking-status-form');
                const statusSelect = document.getElementById('booking-status-value');
                const assigneeSelect = document.getElementById('booking-assigned-to');
                const reference = modal?.querySelector('[data-booking-modal-reference]');
                const errorAlert = modal?.querySelector('[data-booking-update-error]');
                const feedback = document.getElementById('booking-update-feedback');
                if (!table || !modal || !form || !statusSelect || !reference || !errorAlert || !feedback) return;

                const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
                let submitting = false;

                table.addEventListener('click', (event) => {
                    if (!(event.target instanceof Element)) return;
                    const trigger = event.target.closest('[data-booking-update]');
                    if (!trigger) return;

                    form.action = trigger.dataset.bookingUpdateUrl;
                    statusSelect.value = trigger.dataset.bookingStatus || '';
                    if (assigneeSelect) assigneeSelect.value = trigger.dataset.bookingAssignedTo || '';
                    reference.textContent = trigger.dataset.bookingReference || '';
                    errorAlert.textContent = '';
                    errorAlert.classList.add('d-none');
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (submitting) return;

                    submitting = true;
                    form.setAttribute('aria-busy', 'true');
                    const submitButton = form.querySelector('[data-booking-update-submit]');
                    const originalLabel = submitButton.textContent;
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving…';
                    errorAlert.textContent = '';
                    errorAlert.classList.add('d-none');

                    const payload = Object.fromEntries(new FormData(form).entries());
                    delete payload._token;
                    delete payload._method;
                    if (payload.assigned_to === '') payload.assigned_to = null;

                    try {
                        const response = await fetch(form.action, {
                            method: 'PUT',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        let result;
                        try {
                            result = await response.json();
                        } catch {
                            throw new Error(`Could not update booking (HTTP ${response.status}).`);
                        }

                        if (!response.ok) {
                            const validationErrors = Object.values(result.errors || {}).flat();
                            throw new Error(validationErrors.join(' ') || result.message || `Could not update booking (HTTP ${response.status}).`);
                        }

                        feedback.textContent = result.message || 'Booking updated.';
                        feedback.classList.remove('d-none');
                        modalInstance.hide();
                        if (window.jQuery?.fn.DataTable.isDataTable(table)) {
                            window.jQuery(table).DataTable().ajax.reload(null, false);
                        }
                    } catch (error) {
                        errorAlert.textContent = error instanceof Error ? error.message : 'Could not update booking. Please try again.';
                        errorAlert.classList.remove('d-none');
                    } finally {
                        submitting = false;
                        form.removeAttribute('aria-busy');
                        submitButton.disabled = false;
                        submitButton.textContent = originalLabel;
                    }
                });
            })();
        </script>
    @endpush
@endunless