@extends('layouts.app')

@section('title', 'New Repair Ticket')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Support desk" title="New repair ticket" :back-url="route('tickets.index')" back-label="Back to tickets" />
    @if ($customers->isEmpty())<div class="alert alert-warning">Create a customer profile before opening a repair ticket. <a href="{{ route('customers.create') }}">Add customer</a></div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="card p-2" method="POST" action="{{ route('tickets.store') }}">@csrf<div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label" for="customer_id">Customer</label><select class="form-select" id="customer_id" name="customer_id" required><option value="">Select customer</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) old('customer_id', $ticket->customer_id) === (string) $customer->id)>{{ $customer->name }}{{ $customer->company ? ' · '.$customer->company : '' }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="project_id">Related project</label><select class="form-select js-project-search" id="project_id" name="project_id"><option value="">Use customer's project / none</option>@foreach ($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id', $ticket->project_id) === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="device_type">Device type</label><select class="form-select" id="device_type" name="device_type"><option value="">Select type</option>@foreach (['Laptop', 'Desktop PC', 'Workstation', 'Other'] as $deviceType)<option value="{{ $deviceType }}" @selected(old('device_type') === $deviceType)>{{ $deviceType }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="device_model">Make / model</label><input class="form-control" id="device_model" name="device_model" value="{{ old('device_model') }}" maxlength="255"></div>
        <div class="col-md-4"><label class="form-label" for="serial_number">Serial number</label><input class="form-control" id="serial_number" name="serial_number" value="{{ old('serial_number') }}" maxlength="255"></div>
        <div class="col-md-6"><label class="form-label" for="assignees">Assign people</label><input type="hidden" name="assignees_present" value="1"><select class="form-select js-user-search" id="assignees" name="assignees[]" multiple data-placeholder="Search and select people">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('assignees', [])), true))>{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority" required>@foreach ($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', $ticket->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="due_at">Target date</label><input class="form-control" id="due_at" name="due_at" type="date" value="{{ old('due_at') }}"></div>
        <div class="col-12"><label class="form-label" for="issue_description">Reported issue</label><textarea class="form-control" id="issue_description" name="issue_description" rows="5" maxlength="5000" required>{{ old('issue_description') }}</textarea></div>
    </div><div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Cancel</a><button class="btn btn-primary" @disabled($customers->isEmpty())>Create ticket</button></div></form>
</div>
@endsection