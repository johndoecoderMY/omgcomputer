@extends('layouts.app')

@section('title', 'Create Billing Document')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Billing" title="Create {{ $type }}" :back-url="route('billing.index')" back-label="Back to billing" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('billing.store') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="card mb-3"><div class="card-header fw-semibold">Customer and dates</div><div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="customer_name">Customer name</label><input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required></div>
            <div class="col-md-3"><label class="form-label" for="customer_email">Email</label><input class="form-control" id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}"></div>
            <div class="col-md-3"><label class="form-label" for="customer_phone">Phone</label><input class="form-control" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}"></div>
            <div class="col-md-6"><label class="form-label" for="customer_address">Billing address</label><textarea class="form-control" id="customer_address" name="customer_address" rows="2">{{ old('customer_address') }}</textarea></div>
            <div class="col-md-3"><label class="form-label" for="issue_date">Issue date</label><input class="form-control" id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', now()->toDateString()) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="due_date">Due date</label><input class="form-control" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}"></div>
        </div></div>
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Line items</span><button class="btn btn-sm btn-outline-primary" id="add-line" type="button"><span class="fas fa-plus me-1"></span>Add line</button></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th style="min-width:210px">Inventory item</th><th style="min-width:200px">Description</th><th style="width:110px">Quantity</th><th style="width:160px">Unit price</th><th style="width:56px"></th></tr></thead><tbody id="lines">
                <tr class="billing-line"><td><select class="form-select part-select" name="items[0][inventory_part_id]"><option value="">Custom line</option>@foreach ($parts as $part)<option value="{{ $part->id }}" data-name="{{ $part->name }}" data-price="{{ $part->selling_price }}">{{ $part->name }} ({{ $part->stock_quantity }} available)</option>@endforeach</select></td><td><input class="form-control line-description" name="items[0][description]" placeholder="Service or item description"></td><td><input class="form-control" name="items[0][quantity]" type="number" min="1" value="1" required></td><td><input class="form-control line-price" name="items[0][unit_price]" type="number" min="0" step="0.01" placeholder="0.00"></td><td><button class="btn btn-outline-secondary remove-line" type="button" aria-label="Remove line"><span class="fas fa-times"></span></button></td></tr>
            </tbody></table></div>
            <div class="card-body row g-3"><div class="col-md-3"><label class="form-label" for="tax_rate">Tax rate (%)</label><input class="form-control" id="tax_rate" name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate', 0) }}" required></div><div class="col-md-9"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea></div></div>
        </div>
        <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('billing.index') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $type === 'invoice' ? 'Create and issue invoice' : 'Save quotation' }}</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const lines = document.getElementById('lines');
    const renumber = () => lines.querySelectorAll('.billing-line').forEach((row, index) => {
        row.querySelectorAll('[name]').forEach((field) => field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`));
    });
    document.getElementById('add-line').addEventListener('click', () => {
        const row = lines.querySelector('.billing-line').cloneNode(true);
        row.querySelectorAll('input').forEach((field) => field.value = field.type === 'number' && field.name.includes('[quantity]') ? '1' : '');
        row.querySelector('select').value = '';
        lines.append(row);
        renumber();
    });
    lines.addEventListener('change', (event) => {
        if (!event.target.matches('.part-select')) return;
        const option = event.target.selectedOptions[0];
        const row = event.target.closest('.billing-line');
        row.querySelector('.line-description').value = option.dataset.name || '';
        row.querySelector('.line-price').value = option.dataset.price || '';
    });
    lines.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-line');
        if (!button) return;
        if (lines.querySelectorAll('.billing-line').length > 1) button.closest('.billing-line').remove();
        renumber();
    });
})();
</script>
@endpush