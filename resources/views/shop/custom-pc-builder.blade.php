@extends('layouts.app')

@section('title', 'Custom PC builder')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Shop" title="Custom PC builder"
        description="Search the component catalog, add your own RM quote prices, and prepare a draft quotation."
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
                            <p class="small text-body-secondary mb-0">Create one or more PCs in a single quotation. Search all parts, including catalog items with no stock; enter a manual RM quote price when needed. CPU and motherboard compatibility is checked separately for each PC.</p>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button" id="add-pc-build">
                            <i class="bi bi-plus me-1" aria-hidden="true"></i>Add another PC
                        </button>
                    </div>
                    @php($previousBuilds = collect(old('items', []))->groupBy(fn ($line) => $line['build_group'] ?? '1'))
                    @if ($previousBuilds->isEmpty())
                        @php($previousBuilds = collect(['1' => collect()]))
                    @endif
                    <div id="component-builds" class="card-body">
                        @foreach ($previousBuilds as $buildIndex => $buildLines)
                            @php($previousComponents = $buildLines->groupBy('component_type'))
                            <section class="pc-build border rounded p-2 mb-3" data-build-index="{{ $loop->iteration }}">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <h3 class="h6 mb-0 build-title">PC {{ $loop->iteration }}</h3>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-secondary add-component" type="button">Add component</button>
                                        <button class="btn btn-sm btn-outline-danger remove-build" type="button" @disabled($loop->first)>Remove PC</button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead><tr><th style="width:150px">Section</th><th>Component</th><th style="width:105px">Quantity</th><th style="width:150px">Our unit cost (RM)</th><th style="width:160px">Customer unit price (RM)</th><th style="width:56px"></th></tr></thead>
                                        <tbody class="component-lines">
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
                                                <input class="component-build-group" type="hidden" value="{{ $buildIndex }}">
                                            </td>
                                            <td>
                                                @php($selectedComponent = $components->get($type, collect())->firstWhere('id', (int) ($line['inventory_part_id'] ?? 0)))
                                                @php($selectedComponentSupplier = $selectedComponent ? $selectedComponent->suppliers->pluck('name')->merge($selectedComponent->supplier ? [$selectedComponent->supplier->name] : [])->unique()->implode(', ') : '')
                                                <select class="form-select component-select d-none" aria-label="Choose {{ $label }}">
                                                    <option value="">Choose {{ strtolower($label) }}</option>
                                                    @if ($selectedComponent)
                                                        <option value="{{ $selectedComponent->id }}" data-price="{{ $selectedComponent->selling_price }}" data-cost="{{ $selectedComponent->cost_price }}" data-type="{{ $selectedComponent->type }}" data-socket="{{ $selectedComponent->specification('Socket') }}" data-specifications="{{ json_encode($selectedComponent->specifications(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-selection-label="{{ $selectedComponent->name }} · {{ $selectedComponent->stock_quantity }} in stock · RM {{ number_format((float) $selectedComponent->selling_price, 2) }} · {{ $selectedComponentSupplier ?: 'No supplier linked' }}" selected>
                                                            {{ $selectedComponent->name }}
                                                        </option>
                                                    @endif
                                                </select>
                                                <button class="btn btn-outline-primary btn-sm choose-component" type="button">Browse parts</button>
                                                <div class="component-selection small text-body-secondary mt-2" aria-live="polite">
                                                    @if ($selectedComponent)
                                                        {{ $selectedComponent->name }}
                                                        · {{ $selectedComponent->stock_quantity }} in stock
                                                        · RM {{ number_format((float) $selectedComponent->selling_price, 2) }}
                                                        · {{ $selectedComponentSupplier ?: 'No supplier linked' }}
                                                    @else
                                                        No component selected
                                                    @endif
                                                </div>
                                                <div class="component-specifications small text-body-secondary mt-2" aria-live="polite"></div>
                                            </td>
                                            <td><input class="form-control component-quantity" type="number" min="1" max="100000" value="{{ $line['quantity'] ?? 1 }}" aria-label="{{ $label }} quantity"></td>
                                            <td><input class="form-control component-cost" type="number" min="0" max="99999999" step="0.01" value="{{ $line['unit_cost'] ?? '' }}" placeholder="Enter our cost" aria-label="{{ $label }} our unit cost" required></td>
                                            <td><input class="form-control component-price" type="number" min="0" max="99999999" step="0.01" value="{{ $line['unit_price'] ?? '' }}" placeholder="Use RM selling price" aria-label="{{ $label }} customer unit price"></td>
                                            <td><button class="btn btn-outline-secondary remove-component" type="button" aria-label="Remove {{ strtolower($label) }} section"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="small text-body-secondary compatibility-message mt-2" aria-live="polite">Select a motherboard to filter processors by socket. Add a “Socket: …” line to component descriptions to enable compatibility matching.</div>
                            </section>
                        @endforeach
                    </div>
                    <div class="card-body pt-0">
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
                        <div class="d-flex justify-content-between mb-2"><span>Our total cost</span><span id="build-cost-total">RM 0.00</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Estimated gross profit</span><span id="build-gross-profit">RM 0.00</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Tax ({{ number_format((float) old('tax_rate', $taxRate), 2) }}%)</span><span id="build-tax">RM 0.00</span></div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span id="build-total">RM 0.00</span></div>
                        <p class="small text-body-secondary mt-3 mb-0">This creates a draft quotation. Review it in Billing before sending it to the customer.</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Create draft quotation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="component-picker" tabindex="-1" aria-labelledby="component-picker-title" aria-hidden="true"
    data-search-url="{{ $componentSearchUrl }}"
    data-supplier-url="{{ route('shop.custom-pc-builder.components.supplier', ['part' => '__PART__']) }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="component-picker-title">Choose a component</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="component-search-form" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-search">Name or SKU</label>
                        <input class="form-control" id="catalog-search" name="search" maxlength="100">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="catalog-category">Category</label>
                        <select class="form-select" id="catalog-category" name="category">
                            <option value="">All categories</option>
                            @foreach (array_merge($componentTypes, $additionalComponentTypes) as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="catalog-price-min">Min selling price (RM)</label>
                        <input class="form-control" id="catalog-price-min" name="price_min" type="number" min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="catalog-price-max">Max selling price (RM)</label>
                        <input class="form-control" id="catalog-price-max" name="price_max" type="number" min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="catalog-supplier">Supplier source</label>
                        <select class="form-select" id="catalog-supplier" name="supplier">
                            <option value="all">All parts</option>
                            <option value="assigned">Has supplier</option>
                            <option value="unassigned">No supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit">Search parts</button>
                    </div>
                </form>
                <div id="component-search-status" class="small text-body-secondary mb-2" role="status" aria-live="polite"></div>
                <div id="component-search-results" class="list-group"></div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button class="btn btn-sm btn-outline-secondary" id="component-search-previous" type="button">Previous</button>
                    <span class="small text-body-secondary" id="component-search-page"></span>
                    <button class="btn btn-sm btn-outline-secondary" id="component-search-next" type="button">Next</button>
                </div>
                <section id="component-supplier-form-panel" class="border rounded p-3 mt-4 d-none" aria-labelledby="component-supplier-form-title">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0" id="component-supplier-form-title">Add Shopee supplier for <span id="component-supplier-part-name"></span></h3>
                        <button class="btn btn-sm btn-outline-secondary" id="component-supplier-cancel" type="button">Back to parts</button>
                    </div>
                    <form id="component-supplier-form" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <h4 class="h6 mb-0">Supplier details</h4>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-name">Supplier name</label>
                            <input class="form-control" id="new-supplier-name" name="name" type="text" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-contact">Contact person</label>
                            <input class="form-control" id="new-supplier-contact" name="contact_name" type="text" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-email">Email</label>
                            <input class="form-control" id="new-supplier-email" name="email" type="email" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-phone">Phone</label>
                            <input class="form-control" id="new-supplier-phone" name="phone" type="text" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-website">Website</label>
                            <input class="form-control" id="new-supplier-website" name="website" type="url" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-shop-url">Shopee shop account URL</label>
                            <input class="form-control" id="new-supplier-shop-url" name="shopee_shop_url" type="url" maxlength="2048" placeholder="https://shopee.com.my/..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new-supplier-notes">Notes</label>
                            <textarea class="form-control" id="new-supplier-notes" name="notes" rows="2" maxlength="2000"></textarea>
                        </div>
                        <div class="col-12">
                            <h4 class="h6 mb-0">Part listing and pricing</h4>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-product-url">Shopee product URL</label>
                            <input class="form-control" id="new-supplier-product-url" name="shopee_product_url" type="url" maxlength="2048" placeholder="https://shopee.com.my/..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-cost-price">Cost price (RM)</label>
                            <input class="form-control" id="new-supplier-cost-price" name="cost_price" type="number" min="0" max="99999999" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-unit-price">Unit price (RM)</label>
                            <input class="form-control" id="new-supplier-unit-price" name="unit_price" type="number" min="0" max="99999999" step="0.01" required>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">Add and link supplier</button>
                        </div>
                        <div class="col-12">
                            <div id="component-supplier-status" class="small" role="status" aria-live="polite"></div>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>

<template id="component-line-template">
    <tr class="component-line">
        <td>
            <span class="component-category fw-semibold">Additional component</span>
            <div class="component-status small text-body-secondary">Not selected</div>
            <input class="component-type" type="hidden" value="">
            <input class="component-build-group" type="hidden" value="">
        </td>
        <td>
            <select class="form-select component-select d-none" aria-label="Choose an additional PC component">
                <option value="">Choose a component</option>
            </select>
            <button class="btn btn-outline-primary btn-sm choose-component" type="button">Browse parts</button>
            <div class="component-selection small text-body-secondary mt-2" aria-live="polite">No component selected</div>
            <div class="component-specifications small text-body-secondary mt-2" aria-live="polite"></div>
        </td>
        <td><input class="form-control component-quantity" type="number" min="1" max="100000" value="1" aria-label="Component quantity"></td>
        <td><input class="form-control component-cost" type="number" min="0" max="99999999" step="0.01" placeholder="Enter our cost" aria-label="Component our unit cost" required></td>
        <td><input class="form-control component-price" type="number" min="0" max="99999999" step="0.01" placeholder="Use RM selling price" aria-label="Component customer unit price"></td>
        <td><button class="btn btn-outline-secondary remove-component" type="button" aria-label="Remove component"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
    </tr>
</template>

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('custom-pc-form');
        const builds = document.getElementById('component-builds');
        const template = document.getElementById('component-line-template');
        const taxRate = Number(form.querySelector('[name="tax_rate"]').value);
        const picker = document.getElementById('component-picker');
        const pickerModal = bootstrap.Modal.getOrCreateInstance(picker);
        const searchForm = document.getElementById('component-search-form');
        const searchResults = document.getElementById('component-search-results');
        const searchStatus = document.getElementById('component-search-status');
        const pageStatus = document.getElementById('component-search-page');
        const supplierPanel = document.getElementById('component-supplier-form-panel');
        const supplierForm = document.getElementById('component-supplier-form');
        const supplierStatus = document.getElementById('component-supplier-status');
        let activeRow = null;
        let supplierPart = null;
        let currentPage = 1;
        let lastPage = 1;
        let searchRequest = 0;
        const allRows = () => [...builds.querySelectorAll('.component-line')];
        const rowsForBuild = (build) => [...build.querySelectorAll('.component-line')];

        const setBuildLabels = () => [...builds.querySelectorAll('.pc-build')].forEach((build, index) => {
            build.dataset.buildIndex = String(index + 1);
            build.querySelector('.build-title').textContent = `PC ${index + 1}`;
            build.querySelector('.remove-build').disabled = builds.querySelectorAll('.pc-build').length === 1;
        });

        const categoryLabels = {
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
        };

        const updateComponentSpecifications = (row) => {
            const select = row.querySelector('.component-select');
            const option = select.selectedOptions[0];
            const target = row.querySelector('.component-specifications');
            const status = row.querySelector('.component-status');
            row.querySelector('.component-quantity').required = Boolean(select.value);
            row.querySelector('.component-cost').required = Boolean(select.value);
            if (!option?.value) {
                target.textContent = '';
                row.querySelector('.component-selection').textContent = 'No component selected';
                row.querySelector('.component-price').placeholder = 'Choose a part first';
                status.textContent = 'Not selected';
                status.classList.remove('text-success');
                status.classList.add('text-body-secondary');
                return;
            }

            const specifications = JSON.parse(option.dataset.specifications || '{}');
            target.textContent = Object.entries(specifications)
                .map(([label, value]) => `${label}: ${value}`)
                .join(' · ');
            row.querySelector('.component-selection').textContent = option.dataset.selectionLabel || option.textContent.trim();
            row.querySelector('.component-price').placeholder = Number(option.dataset.price) > 0
                ? 'Use RM selling price'
                : 'Enter RM quote price';
            status.textContent = 'Selected';
            status.classList.remove('text-body-secondary');
            status.classList.add('text-success');
        };

        const updateCompatibility = () => {
            let incompatibleSelectionChanged = false;

            builds.querySelectorAll('.pc-build').forEach((build) => {
                const rows = rowsForBuild(build);
                const motherboardOption = rows
                    .map((row) => row.querySelector('.component-select').selectedOptions[0])
                    .find((option) => option?.dataset.type === 'motherboard');
                const motherboardSocket = motherboardOption?.dataset.socket?.trim().toLowerCase();

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
                            row.querySelector('.component-cost').value = '';
                            row.querySelector('.component-price').value = '';
                            incompatibleSelectionChanged = true;
                        }
                    });
                    updateComponentSpecifications(row);
                });

                const compatibilityMessage = build.querySelector('.compatibility-message');
                if (!motherboardOption) {
                    compatibilityMessage.textContent = 'Select a motherboard to filter processors by socket. Add a “Socket: …” line to component descriptions to enable compatibility matching.';
                } else if (!motherboardSocket) {
                    compatibilityMessage.textContent = 'This motherboard has no socket specification. Add a “Socket: …” line to its description before selecting a processor.';
                } else {
                    compatibilityMessage.textContent = `Processors in this PC are filtered to socket ${motherboardOption.dataset.socket}. Check the motherboard manufacturer’s CPU support list and BIOS requirements before purchase.`;
                }
            });

            return incompatibleSelectionChanged;
        };

        const updateEstimate = () => {
            let subtotal = 0;
            let costTotal = 0;
            allRows().forEach((row) => {
                const select = row.querySelector('.component-select');
                const quantity = Number(row.querySelector('.component-quantity').value) || 0;
                const priceInput = row.querySelector('.component-price');
                const costInput = row.querySelector('.component-cost');
                const selectedOption = select.selectedOptions[0];
                const price = priceInput.value === '' ? Number(selectedOption?.dataset.price || 0) : Number(priceInput.value);
                const cost = Number(costInput.value) || 0;
                subtotal += quantity * price;
                costTotal += quantity * cost;
            });
            const tax = Math.round(subtotal * taxRate) / 100;
            document.getElementById('build-subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('build-cost-total').textContent = `RM ${costTotal.toFixed(2)}`;
            document.getElementById('build-gross-profit').textContent = `RM ${(subtotal - costTotal).toFixed(2)}`;
            document.getElementById('build-tax').textContent = `RM ${tax.toFixed(2)}`;
            document.getElementById('build-total').textContent = `RM ${(subtotal + tax).toFixed(2)}`;
        };

        const setNames = () => {
            setBuildLabels();
            allRows().forEach((row, index) => {
                row.querySelector('.component-select').name = `items[${index}][inventory_part_id]`;
                row.querySelector('.component-quantity').name = `items[${index}][quantity]`;
                row.querySelector('.component-cost').name = `items[${index}][unit_cost]`;
                row.querySelector('.component-price').name = `items[${index}][unit_price]`;
                row.querySelector('.component-type').name = `items[${index}][component_type]`;
                row.querySelector('.component-build-group').name = `items[${index}][build_group]`;
                row.querySelector('.component-build-group').value = row.closest('.pc-build').dataset.buildIndex;
            });
        };

        const syncComponentSections = () => allRows().forEach((row) => {
            const option = row.querySelector('.component-select').selectedOptions[0];
            const type = option?.dataset.type || row.querySelector('.component-type').value;
            const label = categoryLabels[type];
            if (label) {
                row.querySelector('.component-category').textContent = label;
                row.querySelector('.component-type').value = type;
            }
        });

        const searchParts = async (page = 1) => {
            const requestNumber = ++searchRequest;
            currentPage = page;
            const params = new URLSearchParams(new FormData(searchForm));
            params.set('page', String(page));
            searchStatus.textContent = 'Searching parts…';
            searchResults.replaceChildren();

            try {
                const response = await fetch(`${picker.dataset.searchUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'The parts search failed. Check the filters and try again.');
                }
                if (requestNumber !== searchRequest) return;

                searchStatus.textContent = `${result.meta.total.toLocaleString()} parts found. Prices and price filters are shown in RM.`;
                lastPage = result.meta.last_page;
                pageStatus.textContent = `Page ${result.meta.current_page} of ${lastPage}`;
                document.getElementById('component-search-previous').disabled = result.meta.current_page <= 1;
                document.getElementById('component-search-next').disabled = result.meta.current_page >= lastPage;

                if (result.data.length === 0) {
                    searchResults.textContent = 'No parts match these filters.';
                    return;
                }

                result.data.forEach((part) => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2';
                    const details = document.createElement('div');
                    const name = document.createElement('div');
                    name.className = 'fw-semibold';
                    name.textContent = part.name;
                    const description = document.createElement('div');
                    description.className = 'small text-body-secondary';
                    const sellingPrice = Number(part.selling_price) > 0
                        ? `Selling price RM ${Number(part.selling_price).toFixed(2)}`
                        : 'No RM selling price';
                    const costPrice = Number(part.cost_price) > 0
                        ? `Our cost RM ${Number(part.cost_price).toFixed(2)}`
                        : 'Our cost not set';
                    description.textContent = [
                        categoryLabels[part.type] || part.type,
                        part.sku || 'No SKU',
                        `${part.stock_quantity} in stock`,
                        sellingPrice,
                        costPrice,
                        part.supplier || 'No supplier linked',
                    ].join(' · ');
                    details.append(name, description);
                    (part.supplier_offers || []).forEach((offer) => {
                        const offerDetails = document.createElement('div');
                        offerDetails.className = 'small text-body-secondary';
                        offerDetails.textContent = `${offer.name} · Cost RM ${offer.cost_price === null ? '—' : Number(offer.cost_price).toFixed(2)} · Unit RM ${offer.unit_price === null ? '—' : Number(offer.unit_price).toFixed(2)}`;
                        if (offer.product_url) {
                            const productLink = document.createElement('a');
                            productLink.href = offer.product_url;
                            productLink.target = '_blank';
                            productLink.rel = 'noopener noreferrer';
                            productLink.textContent = 'Shopee product';
                            productLink.className = 'ms-2';
                            offerDetails.append(productLink);
                        }
                        details.append(offerDetails);
                    });
                    const actions = document.createElement('div');
                    actions.className = 'd-flex gap-2';
                    const addSupplier = document.createElement('button');
                    addSupplier.type = 'button';
                    addSupplier.className = 'btn btn-sm btn-outline-secondary';
                    addSupplier.textContent = part.supplier ? 'Add another Shopee supplier' : 'Add Shopee supplier';
                    addSupplier.addEventListener('click', () => {
                        supplierPart = part;
                        document.getElementById('component-supplier-part-name').textContent = part.name;
                        supplierStatus.textContent = '';
                        supplierStatus.className = 'small';
                        supplierPanel.classList.remove('d-none');
                        document.getElementById('component-search-form').classList.add('d-none');
                        searchStatus.classList.add('d-none');
                        searchResults.classList.add('d-none');
                        document.querySelector('#component-picker .d-flex.justify-content-between.align-items-center.mt-3').classList.add('d-none');
                        supplierForm.reset();
                        supplierForm.querySelector('input[name="_token"]').value =
                            document.querySelector('meta[name="csrf-token"]')?.content || supplierForm.querySelector('input[name="_token"]').value;
                    });
                    actions.append(addSupplier);
                    const choose = document.createElement('button');
                    choose.type = 'button';
                    choose.className = 'btn btn-sm btn-outline-primary';
                    choose.textContent = 'Choose';
                    choose.addEventListener('click', () => selectPart(part));
                    actions.append(choose);
                    item.append(details, actions);
                    searchResults.append(item);
                });
            } catch (error) {
                if (requestNumber !== searchRequest) return;
                searchStatus.textContent = error.message || 'The parts search failed. Try again.';
            }
        };

        document.getElementById('component-supplier-cancel').addEventListener('click', () => {
            supplierPanel.classList.add('d-none');
            document.getElementById('component-search-form').classList.remove('d-none');
            searchStatus.classList.remove('d-none');
            searchResults.classList.remove('d-none');
            document.querySelector('#component-picker .d-flex.justify-content-between.align-items-center.mt-3').classList.remove('d-none');
        });

        supplierForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!supplierPart) return;

            const submitButton = supplierForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            supplierStatus.textContent = 'Saving supplier and linking part…';
            supplierStatus.className = 'small text-body-secondary';
            const supplierUrl = picker.dataset.supplierUrl.replace('__PART__', String(supplierPart.id));

            try {
                const response = await fetch(supplierUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': supplierForm.querySelector('input[name="_token"]').value,
                    },
                    body: new FormData(supplierForm),
                });
                const result = await response.json();
                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'The supplier could not be saved.');
                }

                supplierPart.supplier = result.suppliers;
                supplierPart.supplier_ids = result.supplier_ids;
                const supplierFilter = document.getElementById('catalog-supplier');
                if (![...supplierFilter.options].some((option) => option.value === String(result.supplier.id))) {
                    supplierFilter.add(new Option(result.supplier.name, result.supplier.id));
                }
                supplierFilter.value = String(result.supplier.id);
                supplierStatus.textContent = `${result.supplier.name} was added to ${supplierPart.name}.`;
                supplierStatus.className = 'small text-success';
                supplierPanel.classList.add('d-none');
                document.getElementById('component-search-form').classList.remove('d-none');
                searchStatus.classList.remove('d-none');
                searchResults.classList.remove('d-none');
                document.querySelector('#component-picker .d-flex.justify-content-between.align-items-center.mt-3').classList.remove('d-none');
                await searchParts();
            } catch (error) {
                supplierStatus.textContent = error.message || 'The supplier could not be saved. Try again.';
                supplierStatus.className = 'small text-danger';
            } finally {
                submitButton.disabled = false;
            }
        });

        const selectPart = (part) => {
            const select = activeRow.querySelector('.component-select');
            let option = [...select.options].find((candidate) => candidate.value === String(part.id));
            if (!option) {
                option = new Option(part.name, part.id);
                select.add(option);
            }
            option.dataset.price = String(part.selling_price);
            option.dataset.cost = String(part.cost_price);
            option.dataset.type = part.type;
            option.dataset.socket = part.socket || '';
            option.dataset.specifications = JSON.stringify(part.specifications || {});
            option.dataset.selectionLabel = [
                part.name,
                `${part.stock_quantity} in stock`,
                `RM ${Number(part.selling_price).toFixed(2)}`,
                part.supplier || 'No supplier linked',
            ].join(' · ');
            select.value = String(part.id);

            const priceInput = activeRow.querySelector('.component-price');
            priceInput.value = Number(part.selling_price) > 0 ? Number(part.selling_price).toFixed(2) : '';
            priceInput.placeholder = Number(part.selling_price) > 0 ? 'Use RM selling price' : 'Enter RM quote price';
            const costInput = activeRow.querySelector('.component-cost');
            costInput.value = Number(part.cost_price) > 0 ? Number(part.cost_price).toFixed(2) : '';
            activeRow.querySelector('.component-quantity').value = 1;
            syncComponentSections();
            updateCompatibility();
            updateEstimate();
            pickerModal.hide();
        };

        builds.addEventListener('click', (event) => {
            const button = event.target.closest('.choose-component');
            if (button) {
                activeRow = button.closest('.component-line');
                const type = activeRow.querySelector('.component-type').value;
                document.getElementById('catalog-category').value = type;
                document.getElementById('catalog-search').value = '';
                document.getElementById('catalog-price-min').value = '';
                document.getElementById('catalog-price-max').value = '';
                document.getElementById('catalog-supplier').value = 'all';
                pickerModal.show();
                searchParts();
                return;
            }

            const addComponent = event.target.closest('.add-component');
            if (addComponent) {
                const row = template.content.firstElementChild.cloneNode(true);
                addComponent.closest('.pc-build').querySelector('.component-lines').appendChild(row);
                setNames();
                syncComponentSections();
                updateCompatibility();
                updateEstimate();
                return;
            }

            const removeBuild = event.target.closest('.remove-build');
            if (removeBuild && builds.querySelectorAll('.pc-build').length > 1) {
                removeBuild.closest('.pc-build').remove();
                setNames();
                updateCompatibility();
                updateEstimate();
                return;
            }

            const removeButton = event.target.closest('.remove-component');
            if (!removeButton) return;
            const build = removeButton.closest('.pc-build');
            const rows = rowsForBuild(build);
            if (rows.length === 1) {
                const row = rows[0];
                row.querySelector('.component-select').value = '';
                row.querySelector('.component-cost').value = '';
                row.querySelector('.component-price').value = '';
                row.querySelector('.component-price').setCustomValidity('');
                row.querySelector('.component-quantity').value = 1;
            } else {
                removeButton.closest('.component-line').remove();
            }
            setNames();
            syncComponentSections();
            updateCompatibility();
            updateEstimate();
        });

        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            searchParts();
        });
        document.getElementById('component-search-previous').addEventListener('click', () => {
            if (currentPage > 1) searchParts(currentPage - 1);
        });
        document.getElementById('component-search-next').addEventListener('click', () => {
            if (currentPage < lastPage) searchParts(currentPage + 1);
        });

        document.getElementById('add-pc-build').addEventListener('click', () => {
            const build = builds.querySelector('.pc-build').cloneNode(true);
            build.querySelectorAll('.component-line').forEach((row) => {
                row.querySelector('.component-select').replaceChildren(new Option('Choose a component', ''));
                row.querySelector('.component-cost').value = '';
                row.querySelector('.component-price').value = '';
                row.querySelector('.component-price').setCustomValidity('');
                row.querySelector('.component-quantity').value = 1;
                row.querySelector('.component-status').textContent = 'Not selected';
                row.querySelector('.component-status').classList.remove('text-success');
                row.querySelector('.component-status').classList.add('text-body-secondary');
                row.querySelector('.component-selection').textContent = 'No component selected';
                row.querySelector('.component-specifications').textContent = '';
            });
            builds.appendChild(build);
            setNames();
            syncComponentSections();
            updateCompatibility();
            updateEstimate();
        });

        builds.addEventListener('change', (event) => {
            if (event.target.matches('.component-select')) {
                const price = event.target.selectedOptions[0]?.dataset.price;
                const priceInput = event.target.closest('.component-line').querySelector('.component-price');
                priceInput.value = Number(price) > 0 ? Number(price).toFixed(2) : '';
                const cost = event.target.selectedOptions[0]?.dataset.cost;
                const costInput = event.target.closest('.component-line').querySelector('.component-cost');
                costInput.value = Number(cost) > 0 ? Number(cost).toFixed(2) : '';
                syncComponentSections();
                if (event.target.selectedOptions[0]?.dataset.type === 'motherboard') {
                    rowsForBuild(event.target.closest('.pc-build')).forEach((row) => {
                        const select = row.querySelector('.component-select');
                        if (select !== event.target && select.selectedOptions[0]?.dataset.type === 'motherboard') {
                            select.value = '';
                            row.querySelector('.component-cost').value = '';
                            row.querySelector('.component-price').value = '';
                        }
                    });
                }
                updateCompatibility();
            }
            updateEstimate();
        });
        form.addEventListener('submit', (event) => {
            allRows().forEach((row) => {
                if (!row.querySelector('.component-select').value) row.remove();
            });
            for (const row of allRows()) {
                const option = row.querySelector('.component-select').selectedOptions[0];
                const priceInput = row.querySelector('.component-price');
                if (Number(option?.dataset.price || 0) <= 0 && priceInput.value === '') {
                    priceInput.setCustomValidity('Enter an RM quote price for this catalog item.');
                    priceInput.reportValidity();
                    event.preventDefault();
                    return;
                }
                priceInput.setCustomValidity('');
            }
            setNames();
        });
        builds.addEventListener('input', (event) => {
            if (event.target.matches('.component-price')) event.target.setCustomValidity('');
            if (event.target.matches('.component-cost')) event.target.setCustomValidity('');
            updateEstimate();
        });

        setNames();
        updateCompatibility();
        updateEstimate();
    })();
</script>
@endpush
@endsection
