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
        <input type="hidden" name="custom_pc_build" value="1">
        <input type="hidden" name="type" value="quotation">
        <input type="hidden" name="tax_rate" value="{{ old('tax_rate', $taxRate) }}">
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h5 mb-1">Build components</h2>
                            <p class="small text-body-secondary mb-0">Choose in-stock parts, accessories, operating systems, and software. Add extra lines for peripherals such as USB extension cables. Matching CPU and motherboard sockets are enforced; the quotation will not reserve stock.</p>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button" id="add-component">
                            <i class="bi bi-plus me-1" aria-hidden="true"></i>Add another component
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th style="width:170px">Section</th><th>Component</th><th style="width:130px">Quantity</th><th style="width:180px">Unit price (RM)</th><th style="width:56px"></th></tr></thead>
                            <tbody id="component-lines">
                                @php($previousComponents = collect(old('items', []))->groupBy('component_type'))
                                @foreach ($componentTypes as $type => $label)
                                    @php($sectionLines = $previousComponents->get($type, collect())->values())
                                    @if ($sectionLines->isEmpty())
                                        @php($sectionLines = collect([['inventory_part_id' => '', 'quantity' => 1, 'unit_price' => '']]))
                                    @endif
                                    @foreach ($sectionLines as $line)
                                        <tr class="component-line">
                                            <td>
                                                <span class="component-category fw-semibold">{{ $label }}</span>
                                                <div class="component-status small text-body-secondary">Not selected</div>
                                                <input class="component-type" type="hidden" value="{{ $type }}">
                                            </td>
                                            <td>
                                                <select class="form-select component-select" aria-label="Choose {{ $label }}">
                                                    <option value="">Choose {{ strtolower($label) }}</option>
                                                    @foreach ($components->get($type, collect()) as $component)
                                                        <option value="{{ $component->id }}" data-price="{{ $component->selling_price }}" data-type="{{ $component->type }}" data-socket="{{ $component->specification('Socket') }}" data-specifications="{{ json_encode($component->specifications(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" @selected((string) ($line['inventory_part_id'] ?? '') === (string) $component->id)>
                                                            {{ $component->name }} · {{ $component->stock_quantity }} in stock · RM {{ number_format((float) $component->selling_price, 2) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if ($components->get($type, collect())->isEmpty())
                                                    <div class="small text-warning-emphasis mt-1">No in-stock {{ strtolower($label) }} available</div>
                                                @endif
                                                <div class="component-specifications small text-body-secondary mt-2" aria-live="polite"></div>
                                            </td>
                                            <td><input class="form-control component-quantity" type="number" min="1" max="100000" value="{{ $line['quantity'] ?? 1 }}" aria-label="{{ $label }} quantity"></td>
                                            <td><input class="form-control component-price" type="number" min="0" max="99999999" step="0.01" value="{{ $line['unit_price'] ?? '' }}" placeholder="Use catalog price" aria-label="{{ $label }} unit price"></td>
                                            <td><button class="btn btn-outline-secondary remove-component" type="button" aria-label="Remove {{ strtolower($label) }} section"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($components->isEmpty())
                        <div class="card-body"><div class="alert alert-warning mb-0">No in-stock components, accessories, operating systems, or software are available. Add inventory first.</div></div>
                    @endif
                    <div class="card-body pt-0">
                        <div class="small text-body-secondary" id="compatibility-message" aria-live="polite">Select a motherboard to filter processors by socket. Add a “Socket: …” line to component descriptions to enable compatibility matching.</div>
                        <div class="small text-body-secondary mt-2">Software and Windows items marked as demo estimates are examples only. Verify genuine license or subscription availability, region, term, and authorized distributor pricing before quoting.</div>
                    </div>
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
            <span class="component-category fw-semibold">Additional component</span>
            <div class="component-status small text-body-secondary">Not selected</div>
            <input class="component-type" type="hidden" value="">
        </td>
        <td>
            <select class="form-select component-select" aria-label="Choose an additional PC component">
                <option value="">Choose a component</option>
                @foreach (array_merge($componentTypes, $additionalComponentTypes) as $type => $label)
                    @if ($components->has($type))
                        <optgroup label="{{ $label }}">
                            @foreach ($components[$type] as $component)
                                <option value="{{ $component->id }}" data-price="{{ $component->selling_price }}" data-type="{{ $component->type }}" data-socket="{{ $component->specification('Socket') }}" data-specifications="{{ json_encode($component->specifications(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
                                    {{ $component->name }} · {{ $component->stock_quantity }} in stock · RM {{ number_format((float) $component->selling_price, 2) }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
            <div class="component-specifications small text-body-secondary mt-2" aria-live="polite"></div>
        </td>
        <td><input class="form-control component-quantity" type="number" min="1" max="100000" value="1" aria-label="Component quantity"></td>
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
        const compatibilityMessage = document.getElementById('compatibility-message');

        const updateComponentSpecifications = (row) => {
            const select = row.querySelector('.component-select');
            const option = select.selectedOptions[0];
            const target = row.querySelector('.component-specifications');
            const status = row.querySelector('.component-status');
            row.querySelector('.component-quantity').required = Boolean(select.value);
            if (!option?.value) {
                target.textContent = '';
                status.textContent = 'Not selected';
                status.classList.remove('text-success');
                status.classList.add('text-body-secondary');
                return;
            }

            const specifications = JSON.parse(option.dataset.specifications || '{}');
            target.textContent = Object.entries(specifications)
                .map(([label, value]) => `${label}: ${value}`)
                .join(' · ');
            status.textContent = 'Selected';
            status.classList.remove('text-body-secondary');
            status.classList.add('text-success');
        };

        const updateCompatibility = () => {
            const rows = [...lines.querySelectorAll('.component-line')];
            const motherboardOption = rows
                .map((row) => row.querySelector('.component-select').selectedOptions[0])
                .find((option) => option?.dataset.type === 'motherboard');
            const motherboardSocket = motherboardOption?.dataset.socket?.trim().toLowerCase();
            let incompatibleSelectionChanged = false;

            rows.forEach((row) => {
                const select = row.querySelector('.component-select');
                [...select.options].forEach((option) => {
                    if (option.dataset.type !== 'cpu') return;
                    option.disabled = Boolean(motherboardOption) && (
                        !motherboardSocket
                        || !option.dataset.socket
                        || option.dataset.socket.trim().toLowerCase() !== motherboardSocket
                    );
                    if (option.selected && option.disabled) {
                        select.value = '';
                        row.querySelector('.component-price').value = '';
                        incompatibleSelectionChanged = true;
                    }
                });
                updateComponentSpecifications(row);
            });

            if (!motherboardOption) {
                compatibilityMessage.textContent = 'Select a motherboard to filter processors by socket. Add a “Socket: …” line to component descriptions to enable compatibility matching.';
            } else if (!motherboardSocket) {
                compatibilityMessage.textContent = 'This motherboard has no socket specification. Add a “Socket: …” line to its description before selecting a processor.';
            } else {
                compatibilityMessage.textContent = `Processors are filtered to socket ${motherboardOption.dataset.socket}. Check the motherboard manufacturer’s CPU support list and BIOS requirements before purchase.`;
            }

            return incompatibleSelectionChanged;
        };

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
            row.querySelector('.component-type').name = `items[${index}][component_type]`;
        });

        const syncComponentSections = () => lines.querySelectorAll('.component-line').forEach((row) => {
            const option = row.querySelector('.component-select').selectedOptions[0];
            const type = option?.dataset.type || row.querySelector('.component-type').value;
            const label = {
                cpu: 'CPU',
                gpu: 'GPU',
                ram: 'RAM',
                motherboard: 'Motherboard',
                storage: 'Storage',
                power_supply: 'Power supply',
                case: 'Case',
                cooling: 'Cooling',
                operating_system: 'Operating system',
                software: 'Software / Microsoft',
                peripheral: 'Peripheral',
                other: 'Other',
            }[type];
            if (label) {
                row.querySelector('.component-category').textContent = label;
                row.querySelector('.component-type').value = type;
            }
        });

        document.getElementById('add-component').addEventListener('click', () => {
            const row = template.content.firstElementChild.cloneNode(true);
            lines.appendChild(row);
            setNames();
            syncComponentSections();
            updateCompatibility();
            updateEstimate();
        });

        lines.addEventListener('change', (event) => {
            if (event.target.matches('.component-select')) {
                const price = event.target.selectedOptions[0]?.dataset.price;
                const priceInput = event.target.closest('.component-line').querySelector('.component-price');
                priceInput.value = price || '';
                syncComponentSections();
                if (event.target.selectedOptions[0]?.dataset.type === 'motherboard') {
                    lines.querySelectorAll('.component-line').forEach((row) => {
                        const select = row.querySelector('.component-select');
                        if (select !== event.target && select.selectedOptions[0]?.dataset.type === 'motherboard') {
                            select.value = '';
                            row.querySelector('.component-price').value = '';
                        }
                    });
                }
                updateCompatibility();
            }
            updateEstimate();
        });
        form.addEventListener('submit', () => {
            lines.querySelectorAll('.component-line').forEach((row) => {
                if (!row.querySelector('.component-select').value) row.remove();
            });
            setNames();
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
            syncComponentSections();
            updateCompatibility();
            updateEstimate();
        });

        setNames();
        updateCompatibility();
        updateEstimate();
    })();
</script>
@endsection
