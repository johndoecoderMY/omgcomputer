@extends('layouts.app')

@section('title', 'Custom PC builder')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop" title="Custom PC builder"
        description="Choose in-stock components and prepare a draft quotation for your customer."
        :back-url="route('shop.products.index')" :back-label="'Back to shop products'" />
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('billing.store') }}" id="custom-pc-form">
        @csrf
        <input type="hidden" name="type" value="quotation">
        <input type="hidden" name="tax_rate" value="{{ old('tax_rate', $taxRate) }}">
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h5 mb-1">Build components</h2>
                            <p class="small text-body-secondary mb-0">Only in-stock inventory components are listed. The quotation will not reserve stock.</p>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button" id="add-component">
                            <i class="bi bi-plus me-1" aria-hidden="true"></i>Add component
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Component</th><th style="width:130px">Quantity</th><th style="width:180px">Unit price (RM)</th><th style="width:56px"></th></tr></thead>
                            <tbody id="component-lines">
                                @php($selectedComponents = old('items', [['inventory_part_id' => '', 'quantity' => 1, 'unit_price' => '']]))
                                @foreach ($selectedComponents as $index => $line)
                                    <tr class="component-line">
                                        <td>
                                            <select class="form-select component-select" name="items[{{ $index }}][inventory_part_id]" required aria-label="Choose a PC component">
                                                <option value="">Choose a component</option>
                                                @foreach ($componentTypes as $type => $label)
                                                    @if ($components->has($type))
                                                        <optgroup label="{{ $label }}">
                                                            @foreach ($components[$type] as $component)
                                                                <option value="{{ $component->id }}" data-price="{{ $component->selling_price }}" @selected((string) ($line['inventory_part_id'] ?? '') === (string) $component->id)>
                                                                    {{ $component->name }} · {{ $component->stock_quantity }} in stock · RM {{ number_format((float) $component->selling_price, 2) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input class="form-control component-quantity" name="items[{{ $index }}][quantity]" type="number" min="1" max="100000" value="{{ $line['quantity'] ?? 1 }}" required aria-label="Component quantity"></td>
                                        <td><input class="form-control component-price" name="items[{{ $index }}][unit_price]" type="number" min="0" max="99999999" step="0.01" value="{{ $line['unit_price'] ?? '' }}" placeholder="Use catalog price" aria-label="Component unit price"></td>
                                        <td><button class="btn btn-outline-secondary remove-component" type="button" aria-label="Remove component"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($components->isEmpty())
                        <div class="card-body"><div class="alert alert-warning mb-0">No in-stock PC components are available. Add CPU, GPU, RAM, motherboard, storage, power supply, case, or cooling inventory first.</div></div>
                    @endif
                </div>
                <div class="card">
                    <div class="card-header fw-semibold">Customer details</div>
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_name">Customer name</label>
                            <input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_email">Email</label>
                            <input class="form-control" id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_phone">Phone</label>
                            <input class="form-control" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="64">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="due_date">Quotation valid until</label>
                            <input class="form-control" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="customer_address">Billing address</label>
                            <textarea class="form-control" id="customer_address" name="customer_address" rows="2" maxlength="2000">{{ old('customer_address') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="5000">{{ old('notes', 'Custom PC build') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header fw-semibold">Quotation estimate</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span id="build-subtotal">RM 0.00</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Tax ({{ number_format((float) old('tax_rate', $taxRate), 2) }}%)</span><span id="build-tax">RM 0.00</span></div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span id="build-total">RM 0.00</span></div>
                        <p class="small text-body-secondary mt-3 mb-0">This creates a draft quotation. Review it in Billing before sending it to the customer.</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary w-100" type="submit" @disabled($components->isEmpty())>
                            <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Create draft quotation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="component-line-template">
    <tr class="component-line">
        <td>
            <select class="form-select component-select" required aria-label="Choose a PC component">
                <option value="">Choose a component</option>
                @foreach ($componentTypes as $type => $label)
                    @if ($components->has($type))
                        <optgroup label="{{ $label }}">
                            @foreach ($components[$type] as $component)
                                <option value="{{ $component->id }}" data-price="{{ $component->selling_price }}">
                                    {{ $component->name }} · {{ $component->stock_quantity }} in stock · RM {{ number_format((float) $component->selling_price, 2) }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
        </td>
        <td><input class="form-control component-quantity" type="number" min="1" max="100000" value="1" required aria-label="Component quantity"></td>
        <td><input class="form-control component-price" type="number" min="0" max="99999999" step="0.01" placeholder="Use catalog price" aria-label="Component unit price"></td>
        <td><button class="btn btn-outline-secondary remove-component" type="button" aria-label="Remove component"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
    </tr>
</template>

<script>
    (() => {
        const form = document.getElementById('custom-pc-form');
        const lines = document.getElementById('component-lines');
        const template = document.getElementById('component-line-template');
        const taxRate = Number(form.querySelector('[name="tax_rate"]').value);

        const updateEstimate = () => {
            let subtotal = 0;
            lines.querySelectorAll('.component-line').forEach((row) => {
                const select = row.querySelector('.component-select');
                const quantity = Number(row.querySelector('.component-quantity').value) || 0;
                const priceInput = row.querySelector('.component-price');
                const selectedOption = select.selectedOptions[0];
                const price = priceInput.value === '' ? Number(selectedOption?.dataset.price || 0) : Number(priceInput.value);
                subtotal += quantity * price;
            });
            const tax = Math.round(subtotal * taxRate) / 100;
            document.getElementById('build-subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('build-tax').textContent = `RM ${tax.toFixed(2)}`;
            document.getElementById('build-total').textContent = `RM ${(subtotal + tax).toFixed(2)}`;
        };

        const setNames = () => lines.querySelectorAll('.component-line').forEach((row, index) => {
            row.querySelector('.component-select').name = `items[${index}][inventory_part_id]`;
            row.querySelector('.component-quantity').name = `items[${index}][quantity]`;
            row.querySelector('.component-price').name = `items[${index}][unit_price]`;
        });

        document.getElementById('add-component').addEventListener('click', () => {
            const row = template.content.firstElementChild.cloneNode(true);
            lines.appendChild(row);
            setNames();
            updateEstimate();
        });

        lines.addEventListener('change', (event) => {
            if (event.target.matches('.component-select')) {
                const price = event.target.selectedOptions[0]?.dataset.price;
                const priceInput = event.target.closest('.component-line').querySelector('.component-price');
                priceInput.value = price || '';
            }
            updateEstimate();
        });
        lines.addEventListener('input', updateEstimate);
        lines.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-component');
            if (!removeButton) return;
            if (lines.querySelectorAll('.component-line').length === 1) {
                const row = removeButton.closest('.component-line');
                row.querySelector('.component-select').value = '';
                row.querySelector('.component-price').value = '';
                row.querySelector('.component-quantity').value = 1;
            } else {
                removeButton.closest('.component-line').remove();
            }
            setNames();
            updateEstimate();
        });

        setNames();
        updateEstimate();
    })();
</script>
@endsection
