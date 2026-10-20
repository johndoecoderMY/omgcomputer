@extends('layouts.app')

@section('title', ($editing ? 'Edit ' : 'Add ').strtolower($categoryLabel))

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$categoryLabel" :title="($editing ? 'Edit ' : 'Add ').strtolower($categoryLabel)"
        :back-url="route('expenses.index', $category)" back-label="Back to {{ strtolower($categoryLabel) }}" />

    <form class="card p-2" method="POST" action="{{ $formAction }}">
        @csrf
        @if ($editing)@method('PUT')@endif
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Item name</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name', $expense->name) }}" maxlength="255" required
                        placeholder="{{ $category === 'services' ? 'SSM, server hosting, domain renewal…' : 'e.g. Office rent or electricity' }}">
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vendor">Provider or payee <span class="text-body-secondary">(optional)</span></label>
                    <input class="form-control" id="vendor" name="vendor" value="{{ old('vendor', $expense->vendor) }}" maxlength="255">
                    @error('vendor')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="amount">Amount (RM)</label>
                    <input class="form-control" id="amount" name="amount" type="number" min="0.01" step="0.01"
                        value="{{ old('amount', $expense->amount) }}" required>
                    @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="frequency">Frequency</label>
                    <select class="form-select" id="frequency" name="frequency" required>
                        @foreach (\App\Models\BusinessExpense::FREQUENCIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('frequency', $expense->frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('frequency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="due_date">Next due date <span class="text-body-secondary">(optional for one-time)</span></label>
                    <input class="form-control" id="due_date" name="due_date" type="date"
                        value="{{ old('due_date', $expense->due_date?->format('Y-m-d')) }}">
                    @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes <span class="text-body-secondary">(optional)</span></label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes', $expense->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <p class="form-text mt-3 mb-0">When a recurring item is fully paid, its next due date advances automatically. Partial payments stay on the current cycle.</p>
        </div>
        <div class="card-footer bg-body d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('expenses.index', $category) }}">Cancel</a>
            <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Add item' }}</button>
        </div>
    </form>
</div>
@endsection
