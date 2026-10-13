@extends('layouts.app')

@section('title', 'Create Repair Ticket')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Customer service" title="Create repair ticket" description="Record customer contact details, device information, and the reported problem." :breadcrumbs="[['label' => 'Repair support desk', 'url' => route('tickets.index')], ['label' => 'Create repair ticket']]" :back-url="route('tickets.index')" back-label="Back to tickets" />

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-1">Please check the ticket details:</p>
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-xl-8">
                    <section class="card mb-4">
                        <div class="card-header"><h2 class="h5 mb-0">Customer details</h2></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label" for="customer-name">Customer name</label><input class="form-control" id="customer-name" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" maxlength="180" required></div>
                                <div class="col-md-6"><label class="form-label" for="customer-phone">Phone</label><input class="form-control" id="customer-phone" name="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}" maxlength="50"></div>
                                <div class="col-12"><label class="form-label" for="customer-email">Email</label><input class="form-control" id="customer-email" name="customer_email" type="email" value="{{ old('customer_email', $customer?->email) }}" maxlength="255"></div>
                            </div>
                        </div>
                    </section>
                    <section class="card mb-4">
                        <div class="card-header"><h2 class="h5 mb-0">Device and reported issue</h2></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label" for="device-type">Device type</label><select class="form-select" id="device-type" name="device_type" required><option value="">Choose device</option>@foreach (['Laptop', 'Desktop PC', 'All-in-one PC', 'Other'] as $device)<option value="{{ $device }}" @selected(old('device_type') === $device)>{{ $device }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="form-label" for="device-model">Make / model</label><input class="form-control" id="device-model" name="device_model" value="{{ old('device_model') }}" maxlength="180" placeholder="e.g. Lenovo ThinkPad T14"></div>
                                <div class="col-md-4"><label class="form-label" for="serial-number">Serial number</label><input class="form-control" id="serial-number" name="serial_number" value="{{ old('serial_number') }}" maxlength="120"></div>
                                <div class="col-12"><label class="form-label" for="subject">Issue summary</label><input class="form-control" id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" required placeholder="e.g. Laptop overheats and shuts down"></div>
                                <div class="col-12"><label class="form-label" for="issue-description">Customer's description</label><textarea class="form-control" id="issue-description" name="issue_description" rows="5" maxlength="5000" required>{{ old('issue_description') }}</textarea><div class="form-text">Record the reported symptoms and any accessories received with the device.</div></div>
                                <div class="col-12"><label class="form-label" for="ticket-images">Device pictures</label><input class="form-control" id="ticket-images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><div class="form-text">Up to 5 images, 5 MB each (JPG, PNG, WebP).</div></div>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-xl-4">
                    <section class="card mb-4">
                        <div class="card-header"><h2 class="h5 mb-0">Ticket details</h2></div>
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority" required>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label" for="assigned-to">Assign technician</label><select class="form-select" id="assigned-to" name="assigned_to"><option value="">Unassigned</option>@foreach ($teamMembers as $member)<option value="{{ $member->id }}" @selected((string) old('assigned_to') === (string) $member->id)>{{ $member->name }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label" for="due-date">Target completion date</label><input class="form-control" id="due-date" name="due_date" type="date" value="{{ old('due_date') }}"></div>
                            <div class="alert alert-info fs-10 mb-0">The ticket starts in <strong>Received</strong>. Add diagnosis, estimate, and status updates from its ticket page.</div>
                        </div>
                    </section>
                    <button class="btn btn-primary w-100" type="submit"><span class="fas fa-ticket-alt me-2"></span>Create ticket</button>
                </div>
            </div>
        </form>
    </div>
@endsection
