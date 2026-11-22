@extends('layouts.app')

@section('title', 'Custom PC builder')

@push('styles')
    <style>
        .performance-estimate {
            color: #183153;
            background-color: #eaf3ff;
            border-color: #bfd7ff !important;
        }

        .performance-estimate h4,
        .performance-estimate .text-body-secondary {
            color: #435f80 !important;
        }
    </style>
@endpush

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
                            <p class="small text-body-secondary mb-0">Create one or more PCs in a single quotation. Search all parts, including catalog items with no stock; enter a manual RM quote price when needed. Starter profiles suggest targets only and never select parts or set prices. CPU and motherboard compatibility is checked separately for each PC.</p>
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
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <label class="small text-body-secondary d-flex align-items-center gap-2 mb-0">
                                            <span>Starter profile</span>
                                            <select class="form-select form-select-sm w-auto build-template-select" aria-label="Starter profile for PC {{ $loop->iteration }}">
                                                <option value="">Choose a guide</option>
                                                <option value="basic">Basic / low-end</option>
                                                <option value="mid">Mid-range</option>
                                                <option value="high">High-end</option>
                                            </select>
                                        </label>
                                        <button class="btn btn-sm btn-outline-secondary add-component" type="button">Add component</button>
                                        <button class="btn btn-sm btn-outline-danger remove-build" type="button" @disabled($loop->first)>Remove PC</button>
                                    </div>
                                </div>
                                <div class="build-template-guidance small text-body-secondary mt-2" role="status" aria-live="polite">Choose a profile to see suggested specs. This guide will not add parts or prices.</div>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead><tr><th style="width:150px">Section</th><th>Component</th><th style="width:105px">Quantity</th><th style="width:150px">Our unit cost (RM)</th><th style="width:160px">Customer unit price (RM)</th><th style="width:56px"></th></tr></thead>
                                        <tbody class="component-lines">
                                @foreach ($componentTypes as $type => $label)
                                    @php($sectionLines = $previousComponents->get($type, collect())->values())
                                    @if ($sectionLines->isEmpty())
                                        @php($sectionLines = collect([['custom_pc_component_id' => '', 'quantity' => 1, 'unit_price' => '']]))
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
                                                @php($selectedComponent = $components->get($type, collect())->firstWhere('id', (int) ($line['custom_pc_component_id'] ?? 0)))
                                                @php($selectedComponentSupplier = $selectedComponent ? $selectedComponent->suppliers->pluck('name')->unique()->implode(', ') : '')
                                                <select class="form-select component-select d-none" aria-label="Choose {{ $label }}">
                                                    <option value="">Choose {{ strtolower($label) }}</option>
                                                    @if ($selectedComponent)
                                                            <option value="{{ $selectedComponent->id }}" data-price="{{ $selectedComponent->selling_price }}" data-cost="{{ $selectedComponent->cost_price }}" data-type="{{ $selectedComponent->type }}" data-socket="{{ $selectedComponent->specification('Socket') }}" data-specifications="{{ json_encode($selectedComponent->specifications(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-selection-label="{{ $selectedComponent->name }} · Builder catalogue · RM {{ number_format((float) $selectedComponent->selling_price, 2) }} · {{ $selectedComponentSupplier ?: 'No supplier linked' }}" selected>
                                                            {{ $selectedComponent->name }}
                                                        </option>
                                                    @endif
                                                </select>
                                                <button class="btn btn-outline-primary btn-sm choose-component" type="button">Browse parts</button>
                                                <div class="component-selection small text-body-secondary mt-2" aria-live="polite">
                                                    @if ($selectedComponent)
                                                        {{ $selectedComponent->name }}
                                                        · Builder catalogue (not inventory stock)
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
                                <div class="performance-estimate alert alert-light border mt-3 mb-0">
                                    <h4 class="h6 mb-2">Approximate performance</h4>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="small fw-semibold">Gaming</div>
                                            <div class="small" data-performance-gaming role="status" aria-live="polite">Select a graphics card to estimate gaming performance.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="small fw-semibold">Work and multitasking</div>
                                            <div class="small" data-performance-work role="status" aria-live="polite">Select a CPU and RAM with specifications to estimate work performance.</div>
                                        </div>
                                    </div>
                                    <p class="small text-body-secondary mt-2 mb-0">Rough guidance, not a benchmark or guaranteed FPS. Gaming uses recognized GPU model families; work uses CPU core count and RAM capacity. Actual results vary by exact model, cooling, software, resolution, and settings.</p>
                                </div>
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
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-label mb-0" for="discount_amount">Discount (RM)</label>
                            <input class="form-control form-control-sm text-end" id="discount_amount" name="discount_amount" type="number" min="0" step="0.01"
                                value="{{ old('discount_amount', 0) }}" style="max-width: 10rem">
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success"><span>Discount applied</span><span id="build-discount">RM 0.00</span></div>
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
    <div class="card mt-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1">Builder parts catalogue</h2>
                <p class="small text-body-secondary mb-0">Manage catalogue parts separately from tracked inventory.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('shop.custom-pc-builder.parts') }}">Manage builder parts</a>
        </div>
    </div>
</div>

<div class="modal fade" id="component-picker" tabindex="-1" aria-labelledby="component-picker-title" aria-hidden="true"
    data-search-url="{{ $componentSearchUrl }}"
    data-component-store-url="{{ $componentStoreUrl }}"
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
                <div id="component-profile-guidance" class="alert alert-info py-2 px-3 mb-3 d-none" role="status" aria-live="polite"></div>
                <div id="component-search-status" class="small text-body-secondary mb-2" role="status" aria-live="polite"></div>
                <div id="component-search-results" class="list-group"></div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button class="btn btn-sm btn-outline-secondary" id="component-search-previous" type="button">Previous</button>
                    <span class="small text-body-secondary" id="component-search-page"></span>
                    <button class="btn btn-sm btn-outline-secondary" id="component-search-next" type="button">Next</button>
                </div>
                <details class="border rounded p-3 mt-4">
                    <summary class="fw-semibold">Add a part to the builder catalogue</summary>
                    <form id="component-create-form" class="row g-3 mt-1">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label" for="new-component-name">Part name</label>
                            <input class="form-control" id="new-component-name" name="name" maxlength="255" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="new-component-sku">SKU (optional)</label>
                            <input class="form-control" id="new-component-sku" name="sku" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="new-component-type">Category</label>
                            <select class="form-select" id="new-component-type" name="type" required>
                                @foreach (array_merge($componentTypes, $additionalComponentTypes) as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="new-component-description">Specifications / notes</label>
                            <textarea class="form-control" id="new-component-description" name="description" rows="2" maxlength="5000" placeholder="Include compatibility details such as Socket: AM5"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="new-component-cost">Our estimated cost (RM)</label>
                            <input class="form-control" id="new-component-cost" name="cost_price" type="number" min="0" max="99999999" step="0.01" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="new-component-price">Customer price (RM)</label>
                            <input class="form-control" id="new-component-price" name="selling_price" type="number" min="0" max="99999999" step="0.01" value="0" required>
                        </div>
                        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div id="component-create-status" class="small" role="status" aria-live="polite"></div>
                            <button class="btn btn-primary" type="submit">Add to builder catalogue</button>
                        </div>
                    </form>
                    <p class="small text-body-secondary mt-2 mb-0">This catalogue is separate from tracked inventory. Add suppliers and pricing after saving a part.</p>
                </details>
                <section id="component-supplier-form-panel" class="border rounded p-3 mt-4 d-none" aria-labelledby="component-supplier-form-title">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0" id="component-supplier-form-title">Add supplier for <span id="component-supplier-part-name"></span></h3>
                        <button class="btn btn-sm btn-outline-secondary" id="component-supplier-cancel" type="button">Back to parts</button>
                    </div>
                    <form id="component-supplier-form" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label" for="new-supplier-type">Supplier type</label>
                            <select class="form-select" id="new-supplier-type" name="supplier_type" required>
                                <option value="shopee">Shopee marketplace</option>
                                <option value="other">Other supplier</option>
                            </select>
                        </div>
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
                        <div class="col-md-6" data-supplier-type-fields="shopee">
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
                        <div class="col-md-6" data-supplier-type-fields="shopee">
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
        const supplierType = supplierForm.querySelector('select[name="supplier_type"]');
        const supplierStatus = document.getElementById('component-supplier-status');
        let activeRow = null;
        let supplierPart = null;
        let currentPage = 1;
        let lastPage = 1;
        let searchRequest = 0;
        const updateSupplierTypeFields = () => {
            const isShopee = supplierType.value === 'shopee';
            supplierForm.querySelectorAll('[data-supplier-type-fields="shopee"]').forEach((field) => {
                field.classList.toggle('d-none', !isShopee);
                field.querySelectorAll('input').forEach((input) => {
                    input.disabled = !isShopee;
                });
            });
        };
        supplierType.addEventListener('change', updateSupplierTypeFields);
        updateSupplierTypeFields();
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

        const buildTemplates = {
            basic: {
                name: 'Basic / low-end',
                use: 'For office, study, browsing, and light or esports gaming.',
                targets: [
                    'CPU: modern 4–6 cores; match the motherboard socket.',
                    'Graphics: integrated for office use, or an entry-level GPU for 1080p esports and lighter games.',
                    'Memory: 16 GB dual-channel, compatible with the motherboard.',
                    'Storage: 500 GB NVMe SSD.',
                    'Power, case, and cooling: quality 500–650 W PSU sized to the exact GPU, airflow case, and compatible cooler.',
                ],
                browseTargets: {
                    cpu: { text: 'Look for a modern 4–6-core CPU; confirm motherboard socket and BIOS support.', metric: 'cores', min: 4, max: 6 },
                    gpu: { text: 'For office use, integrated graphics may be enough; for games, look for an entry-level GPU and check the exact model.' },
                    ram: { text: 'Target 16 GB of compatible dual-channel memory.', metric: 'capacityGb', min: 16, max: 16 },
                    motherboard: { text: 'Match the selected CPU socket, memory generation, and case form factor.' },
                    storage: { text: 'Target a 500 GB NVMe SSD.', metric: 'capacityGb', min: 450, max: 750, requiresNvme: true },
                    power_supply: { text: 'Target a quality 500–650 W PSU; verify the exact GPU power recommendation and connectors.', metric: 'wattage', min: 500, max: 650 },
                    case: { text: 'Check motherboard form factor, GPU length, and cooler clearance.' },
                    cooling: { text: 'Check CPU socket support and case clearance; the stock cooler may be enough for some CPUs.' },
                },
            },
            mid: {
                name: 'Mid-range',
                use: 'For 1080p gaming, everyday productivity, and mainstream editing.',
                targets: [
                    'CPU: modern 6–8 cores; verify socket and BIOS support.',
                    'Graphics: mainstream GPU; 1080p high or tuned 1440p settings are reasonable starting targets.',
                    'Memory: 32 GB dual-channel, compatible with the motherboard.',
                    'Storage: 1 TB NVMe SSD.',
                    'Power, case, and cooling: quality 650–750 W PSU sized to the exact GPU, with suitable airflow and cooler clearance.',
                ],
                browseTargets: {
                    cpu: { text: 'Look for a modern 6–8-core CPU; confirm motherboard socket and BIOS support.', metric: 'cores', min: 6, max: 8 },
                    gpu: { text: 'Look for a mainstream GPU suited to 1080p high or tuned 1440p; check the exact model and games.' },
                    ram: { text: 'Target 32 GB of compatible dual-channel memory.', metric: 'capacityGb', min: 32, max: 32 },
                    motherboard: { text: 'Match the selected CPU socket, memory generation, and case form factor.' },
                    storage: { text: 'Target a 1 TB NVMe SSD.', metric: 'capacityGb', min: 900, max: 1200, requiresNvme: true },
                    power_supply: { text: 'Target a quality 650–750 W PSU; verify the exact GPU power recommendation and connectors.', metric: 'wattage', min: 650, max: 750 },
                    case: { text: 'Check motherboard form factor, GPU length, and cooler clearance.' },
                    cooling: { text: 'Check CPU socket support, cooling needs, and case clearance.' },
                },
            },
            high: {
                name: 'High-end',
                use: 'For demanding 1440p gaming, heavier editing or 3D work, and multitasking.',
                targets: [
                    'CPU: modern 8–12+ cores, chosen for the target applications.',
                    'Graphics: high-end GPU; 12 GB+ VRAM is a useful target for higher resolutions and larger creative workloads.',
                    'Memory: 32–64 GB, based on the applications and project sizes.',
                    'Storage: 2 TB NVMe SSD, with additional drives if large media storage is needed.',
                    'Power, case, and cooling: quality PSU sized to the GPU manufacturer recommendation, plus verified GPU, cooler, and case clearances.',
                ],
                browseTargets: {
                    cpu: { text: 'Look for a modern 8-core or higher CPU suited to the target applications.', metric: 'cores', min: 8 },
                    gpu: { text: 'Look for a high-end GPU; 12 GB or more of VRAM is a useful starting target, but check the exact model.', metric: 'vramGb', min: 12 },
                    ram: { text: 'Target 32–64 GB of compatible memory, based on the applications and project sizes.', metric: 'capacityGb', min: 32, max: 64 },
                    motherboard: { text: 'Match the selected CPU socket and memory generation; check expansion and storage needs.' },
                    storage: { text: 'Target a 2 TB or larger NVMe SSD.', metric: 'capacityGb', min: 1800, requiresNvme: true },
                    power_supply: { text: 'Use the GPU manufacturer’s PSU recommendation; 850 W or higher is a starting point for many high-end builds.', metric: 'wattage', min: 850 },
                    case: { text: 'Verify motherboard form factor, high-end GPU length, and radiator/cooler clearance.' },
                    cooling: { text: 'Match cooler capacity and socket support to the CPU, and verify radiator and case clearance.' },
                },
            },
        };

        const getPartSpecification = (part, labels) => {
            const expectedLabels = new Set(labels.map((label) => label.toLowerCase().replace(/[^a-z0-9]/g, '')));
            const entry = Object.entries(part.specifications || {}).find(([label]) =>
                expectedLabels.has(label.toLowerCase().replace(/[^a-z0-9]/g, '')),
            );
            return entry ? String(entry[1]) : null;
        };

        const getNumericPartSpecification = (part, labels) => {
            const value = getPartSpecification(part, labels);
            const match = value?.match(/^\s*(\d+(?:\.\d+)?)/);
            return match ? Number(match[1]) : null;
        };

        const getCapacityInGb = (value) => {
            const match = value?.match(/^\s*(\d+(?:\.\d+)?)\s*(TB|GB|MB)\b/i);
            if (!match) return null;
            const amount = Number(match[1]);
            const unit = match[2].toUpperCase();
            return unit === 'TB' ? amount * 1000 : unit === 'MB' ? amount / 1000 : amount;
        };

        const matchesProfileTarget = (part, target) => {
            if (!target?.metric) return false;

            let value = null;
            if (target.metric === 'cores') {
                value = getNumericPartSpecification(part, ['Cores', 'Core count']);
            } else if (target.metric === 'capacityGb') {
                value = getCapacityInGb(getPartSpecification(part, ['Capacity']));
            } else if (target.metric === 'wattage') {
                value = getNumericPartSpecification(part, ['Wattage', 'Watts', 'Power output']);
            } else if (target.metric === 'vramGb') {
                value = getCapacityInGb(getPartSpecification(part, ['VRAM']));
            }

            if (value === null || value < target.min || (target.max !== undefined && value > target.max)) {
                return false;
            }

            if (target.requiresNvme) {
                const specifications = Object.entries(part.specifications || {}).map(([label, spec]) => `${label} ${spec}`).join(' ');
                if (!/\bnvme\b/i.test(`${specifications} ${part.description || ''}`)) return false;
            }

            return true;
        };

        const updateComponentProfileGuidance = () => {
            const target = document.getElementById('component-profile-guidance');
            const build = activeRow?.closest('.pc-build');
            const profileKey = build?.querySelector('.build-template-select')?.value;
            const profile = buildTemplates[profileKey];
            const category = document.getElementById('catalog-category').value;
            const recommendation = profile?.browseTargets[category];
            target.replaceChildren();

            if (!profile || !recommendation) {
                target.classList.add('d-none');
                return null;
            }

            const heading = document.createElement('strong');
            heading.textContent = `${profile.name} guide · ${categoryLabels[category] || category}: `;
            const description = document.createElement('span');
            description.textContent = recommendation.text;
            const note = document.createElement('div');
            note.className = 'small mt-1';
            note.textContent = 'A “Matches profile spec” badge checks only the listed measurable target. Verify compatibility, exact model, price, and availability before choosing.';
            target.append(heading, description, note);
            target.classList.remove('d-none');
            return { profile, recommendation };
        };

        const updateBuildTemplateGuidance = (build) => {
            const target = build.querySelector('.build-template-guidance');
            const profile = buildTemplates[build.querySelector('.build-template-select').value];
            target.replaceChildren();

            if (!profile) {
                target.className = 'build-template-guidance small text-body-secondary mt-2';
                target.textContent = 'Choose a profile to see suggested specs. This guide will not add parts or prices.';
                return;
            }

            target.className = 'build-template-guidance alert alert-info py-2 px-3 mt-2 mb-0';
            const heading = document.createElement('strong');
            heading.className = 'd-block mb-1';
            heading.textContent = `${profile.name}: ${profile.use}`;
            const note = document.createElement('p');
            note.className = 'small mb-2';
            note.textContent = 'To use this guide, select exact catalogue items with Browse parts and set their prices yourself. No parts or prices are added automatically.';
            const list = document.createElement('ul');
            list.className = 'small mb-0 ps-3';
            profile.targets.forEach((item) => {
                const listItem = document.createElement('li');
                listItem.textContent = item;
                list.appendChild(listItem);
            });

            target.append(heading, note, list);
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

        const getSelectedParts = (build) => rowsForBuild(build).flatMap((row) => {
            const option = row.querySelector('.component-select').selectedOptions[0];
            if (!option?.value) return [];

            return [{
                type: option.dataset.type || row.querySelector('.component-type').value,
                name: option.textContent.trim(),
                specifications: JSON.parse(option.dataset.specifications || '{}'),
                quantity: Number(row.querySelector('.component-quantity').value),
            }];
        });

        const getSpecification = (part, ...labels) => {
            const entry = Object.entries(part.specifications).find(([label]) => (
                labels.includes(label.toLowerCase())
            ));
            return entry?.[1] || '';
        };

        const parseNumber = (value) => {
            const match = String(value ?? '').match(/[\d,.]+/);
            return match ? Number(match[0].replace(/,/g, '')) : null;
        };

        const parseMemoryCapacity = (value) => {
            const text = String(value || '');
            const kit = text.match(/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*(TB|GB)\b/i);
            if (kit) {
                return Number(kit[1]) * Number(kit[2]) * (kit[3].toUpperCase() === 'TB' ? 1024 : 1);
            }

            const capacity = text.match(/(\d+(?:\.\d+)?)\s*(TB|GB)\b/i);
            if (!capacity) return null;

            return Number(capacity[1]) * (capacity[2].toUpperCase() === 'TB' ? 1024 : 1);
        };

        const classifyGpu = (gpu) => {
            const details = `${gpu.name} ${Object.values(gpu.specifications).join(' ')}`;
            let match = details.match(/\bRTX\s*(\d{4})\s*(TI|SUPER)?\b/i);

            if (match) {
                const model = Number(match[1]);
                const generation = Math.floor(model / 100);
                const classNumber = model % 100;
                if (generation >= 40) {
                    if (classNumber >= 70) return 'high';
                    if (classNumber >= 60) return 'mainstream';
                    return 'entry';
                }
                if (generation >= 20) {
                    if (classNumber >= 80) return 'high';
                    if (classNumber >= 60) return 'mainstream';
                    return 'entry';
                }
            }

            match = details.match(/\bGTX\s*(\d{4})\b/i);
            if (match) {
                const model = Number(match[1]);
                const generation = Math.floor(model / 100);
                const classNumber = model % 100;
                return (generation >= 16 && classNumber >= 60) || (generation >= 10 && classNumber >= 70)
                    ? 'mainstream'
                    : 'entry';
            }

            match = details.match(/\bRX\s*(\d{4})\s*(XTX|XT)?\b/i);
            if (match) {
                const model = Number(match[1]);
                if (model >= 9000) {
                    if (model >= 9070) return 'high';
                    if (model >= 9060) return 'mainstream';
                    return 'entry';
                }
                if (model >= 7000) {
                    if (model >= 7800) return 'high';
                    if (model >= 7600) return 'mainstream';
                    return 'entry';
                }
                if (model >= 6000) {
                    if (model >= 6800) return 'high';
                    if (model >= 6600) return 'mainstream';
                    return 'entry';
                }
                if (model >= 5000) return model >= 5700 ? 'mainstream' : 'entry';
            }

            match = details.match(/\bARC\s+([AB])\s*(\d{3})\b/i);
            if (match) {
                const series = match[1].toUpperCase();
                const model = Number(match[2]);
                if (series === 'B') return model >= 580 ? 'mainstream' : 'entry';
                return model >= 750 ? 'mainstream' : 'entry';
            }

            return null;
        };

        const gamingTierDescriptions = {
            entry: 'Entry GPU class; esports and lighter games at 1080p low-to-medium settings are safer expectations.',
            mainstream: 'Mainstream GPU class; 1080p high or tuned 1440p settings are reasonable starting points.',
            high: 'Higher-end GPU class; 1440p is a reasonable target and 4K depends heavily on the game and settings.',
        };

        const updatePerformanceEstimate = (build) => {
            const parts = getSelectedParts(build);
            const cpu = parts.find((part) => part.type === 'cpu');
            const gpu = parts.find((part) => part.type === 'gpu');
            const ramParts = parts.filter((part) => part.type === 'ram');
            const coreCount = cpu
                ? parseNumber(getSpecification(cpu, 'cores', 'core count'))
                : null;
            const ramCapacities = ramParts.map((part) => {
                const capacity = getSpecification(part, 'capacity', 'memory capacity', 'size') || part.name;
                return parseMemoryCapacity(capacity);
            });
            const ramCapacity = ramCapacities.length && ramCapacities.every((capacity) => capacity !== null)
                ? ramParts.reduce((total, part, index) => {
                    const quantity = Number.isFinite(part.quantity) && part.quantity > 0 ? part.quantity : 1;
                    return total + ramCapacities[index] * quantity;
                }, 0)
                : null;
            const gamingTarget = build.querySelector('[data-performance-gaming]');
            const workTarget = build.querySelector('[data-performance-work]');

            if (!gpu) {
                gamingTarget.textContent = 'Select a graphics card with an exact model name. VRAM capacity alone is not enough to estimate gaming performance.';
            } else {
                const performanceClass = classifyGpu(gpu);
                if (performanceClass === null) {
                    gamingTarget.textContent = 'This GPU model is not recognized. Add its exact model name to the part details for a rough gaming tier.';
                } else {
                    const notes = [];
                    if (!cpu) notes.push('Select a CPU to check for possible CPU limits.');
                    else if (coreCount !== null && coreCount < 6) notes.push('The selected CPU may limit some games.');
                    if (ramCapacity === null) notes.push('Add RAM capacity specs to check memory limits.');
                    else if (ramCapacity < 16) notes.push('Less than 16 GB system RAM may limit newer games.');
                    gamingTarget.textContent = `${gamingTierDescriptions[performanceClass]} ${notes.join(' ')}`;
                }
            }

            if (!cpu) {
                workTarget.textContent = 'Select a CPU to estimate work and multitasking performance.';
                return;
            }
            if (coreCount === null) {
                workTarget.textContent = 'Add a “Cores: 6” specification to the CPU part for a work estimate.';
                return;
            }
            if (ramCapacity === null) {
                workTarget.textContent = 'Select RAM and add its “Capacity: 16 GB” specification for a work estimate.';
                return;
            }

            let workEstimate;
            if (coreCount >= 12 && ramCapacity >= 32) {
                workEstimate = 'Strong for heavy multitasking, compiling, and larger creative workloads.';
            } else if (coreCount >= 8 && ramCapacity >= 16) {
                workEstimate = 'Good for multitasking, development, and mainstream creative work.';
            } else if (coreCount >= 6 && ramCapacity >= 16) {
                workEstimate = 'Balanced for office work, coding, and light-to-moderate editing.';
            } else if (coreCount >= 4 && ramCapacity >= 16) {
                workEstimate = 'Comfortable for everyday office and study; demanding editing or virtual machines may feel limited.';
            } else {
                workEstimate = 'Best for basic office, browsing, and study; heavier multitasking and creative work may be limited.';
            }

            const storageParts = parts.filter((part) => part.type === 'storage');
            const hasSolidStateDrive = storageParts.some((part) => (
                /SSD|NVMe|solid.?state/i.test(`${part.name} ${Object.values(part.specifications).join(' ')}`)
            ));
            if (hasSolidStateDrive) {
                workEstimate += ' An SSD/NVMe is selected for faster boot and app loading.';
            } else if (storageParts.length) {
                workEstimate += ' No SSD/NVMe is identified; storage may slow boot and app loading.';
            } else {
                workEstimate += ' Select storage to assess boot and app loading.';
            }

            workTarget.textContent = workEstimate;
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
            const discountInput = form.querySelector('[name="discount_amount"]');
            const requestedDiscount = Number(discountInput.value) || 0;
            const discount = Math.min(requestedDiscount, subtotal);
            const taxableSubtotal = subtotal - discount;
            discountInput.setCustomValidity(requestedDiscount > subtotal
                ? 'Discount cannot exceed the subtotal.'
                : '');
            const tax = Math.round(taxableSubtotal * taxRate) / 100;
            document.getElementById('build-subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('build-discount').textContent = `- RM ${discount.toFixed(2)}`;
            document.getElementById('build-cost-total').textContent = `RM ${costTotal.toFixed(2)}`;
            document.getElementById('build-gross-profit').textContent = `RM ${(taxableSubtotal - costTotal).toFixed(2)}`;
            document.getElementById('build-tax').textContent = `RM ${tax.toFixed(2)}`;
            document.getElementById('build-total').textContent = `RM ${(taxableSubtotal + tax).toFixed(2)}`;
            builds.querySelectorAll('.pc-build').forEach(updatePerformanceEstimate);
        };

        const setNames = () => {
            setBuildLabels();
            allRows().forEach((row, index) => {
                row.querySelector('.component-select').name = `items[${index}][custom_pc_component_id]`;
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
            const profileGuidance = updateComponentProfileGuidance();

            try {
                const response = await fetch(`${picker.dataset.searchUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'The parts search failed. Check the filters and try again.');
                }
                if (requestNumber !== searchRequest) return;

                searchStatus.textContent = `${result.meta.total.toLocaleString()} catalogue parts found. Customer prices and filters are in RM; source-currency prices are reference only.`;
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
                    item.className = 'list-group-item p-3';
                    const row = document.createElement('div');
                    row.className = 'row g-3 align-items-center';
                    const details = document.createElement('div');
                    details.className = 'col-12 col-lg-7';
                    const heading = document.createElement('div');
                    heading.className = 'd-flex flex-wrap align-items-center gap-2';
                    const name = document.createElement('div');
                    name.className = 'fw-semibold fs-6';
                    name.textContent = part.name;
                    heading.append(name);
                    if (profileGuidance && matchesProfileTarget(part, profileGuidance.recommendation)) {
                        const recommendationBadge = document.createElement('span');
                        recommendationBadge.className = 'badge text-bg-success ms-2';
                        recommendationBadge.textContent = 'Matches profile spec';
                        name.append(recommendationBadge);
                    }
                    const metadata = document.createElement('div');
                    metadata.className = 'd-flex flex-wrap align-items-center gap-2 mt-1';
                    const category = document.createElement('span');
                    category.className = 'badge text-bg-light border text-dark';
                    category.textContent = categoryLabels[part.type] || part.type;
                    const sku = document.createElement('span');
                    sku.className = 'small text-body-secondary';
                    sku.textContent = part.sku ? `SKU: ${part.sku}` : 'No SKU';
                    const catalogue = document.createElement('span');
                    catalogue.className = 'badge text-bg-secondary';
                    catalogue.textContent = 'Builder catalogue · no inventory stock';
                    metadata.append(category, sku, catalogue);

                    const specificationSummary = Object.entries(part.specifications || {})
                        .filter(([label]) => !['name', 'source'].includes(label.toLowerCase().replace(/[^a-z0-9]/g, '')))
                        .slice(0, 5)
                        .map(([label, value]) => `${label}: ${value}`);
                    if (specificationSummary.length > 0) {
                        const specifications = document.createElement('div');
                        specifications.className = 'd-flex flex-wrap gap-1 mt-2';
                        specificationSummary.forEach((specification) => {
                            const badge = document.createElement('span');
                            badge.className = 'badge rounded-pill text-bg-light border text-dark fw-normal';
                            badge.textContent = specification;
                            specifications.append(badge);
                        });
                        details.append(heading, metadata, specifications);
                    } else {
                        details.append(heading, metadata);
                    }

                    const source = getPartSpecification(part, ['Source']);
                    const catalogPrice = Number(part.catalog_price);
                    const catalogCurrency = String(part.catalog_currency || '').toUpperCase();
                    if (source || (catalogPrice > 0 && catalogCurrency)) {
                        const sourceNote = document.createElement('div');
                        sourceNote.className = 'small text-body-secondary mt-2';
                        sourceNote.textContent = source ? `Source: ${source}` : '';
                        if (catalogPrice > 0 && catalogCurrency) {
                            sourceNote.textContent += `${source ? ' · ' : ''}Reference only: ${catalogCurrency} ${catalogPrice.toFixed(2)} (not used as an RM quote price)`;
                        }
                        details.append(sourceNote);
                    }

                    const supplierNames = document.createElement('div');
                    supplierNames.className = 'small text-body-secondary mt-2';
                    supplierNames.textContent = part.supplier ? `Suppliers: ${part.supplier}` : 'No supplier linked';
                    details.append(supplierNames);

                    (part.supplier_offers || []).forEach((offer) => {
                        const offerDetails = document.createElement('div');
                        offerDetails.className = 'small text-body-secondary mt-1';
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

                    const pricing = document.createElement('div');
                    pricing.className = 'col-12 col-sm-6 col-lg-3';
                    const sellingPrice = Number(part.selling_price);
                    const customerPrice = document.createElement('div');
                    customerPrice.className = sellingPrice > 0 ? 'fw-semibold text-success' : 'fw-semibold text-warning-emphasis';
                    customerPrice.textContent = sellingPrice > 0 ? `RM ${sellingPrice.toFixed(2)}` : 'RM quote price needed';
                    const costPrice = document.createElement('div');
                    costPrice.className = 'small text-body-secondary mt-1';
                    costPrice.textContent = Number(part.cost_price) > 0
                        ? `Our cost RM ${Number(part.cost_price).toFixed(2)}`
                        : 'Our cost not set';
                    pricing.append(customerPrice, costPrice);
                    if (sellingPrice <= 0) {
                        const priceNote = document.createElement('div');
                        priceNote.className = 'small text-warning-emphasis';
                        priceNote.textContent = 'Enter a customer price after selecting this part.';
                        pricing.append(priceNote);
                    }

                    const actions = document.createElement('div');
                    actions.className = 'col-12 col-sm-6 col-lg-2 d-flex flex-wrap gap-2 justify-content-sm-end';
                    const addSupplier = document.createElement('button');
                    addSupplier.type = 'button';
                    addSupplier.className = 'btn btn-sm btn-outline-secondary';
                    addSupplier.textContent = 'Add another supplier';
                    addSupplier.setAttribute('aria-label', `Add a supplier for ${part.name}`);
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
                        updateSupplierTypeFields();
                    });
                    actions.append(addSupplier);
                    const choose = document.createElement('button');
                    choose.type = 'button';
                    choose.className = 'btn btn-sm btn-primary';
                    choose.textContent = 'Select part';
                    choose.setAttribute('aria-label', `Select ${part.name}`);
                    choose.addEventListener('click', () => selectPart(part));
                    actions.append(choose);
                    row.append(details, pricing, actions);
                    item.append(row);
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

        const selectPart = (part, closePicker = true) => {
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
            'Builder catalogue (not inventory stock)',
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
            if (closePicker) pickerModal.hide();
        };

        document.getElementById('component-create-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const createForm = event.currentTarget;
            const submitButton = createForm.querySelector('button[type="submit"]');
            const status = document.getElementById('component-create-status');
            submitButton.disabled = true;
            status.textContent = 'Saving builder catalogue part…';
            status.className = 'small text-body-secondary';

            try {
                const response = await fetch(picker.dataset.componentStoreUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': createForm.querySelector('input[name="_token"]').value,
                    },
                    body: new FormData(createForm),
                });
                const result = await response.json();
                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'The part could not be added.');
                }

                status.textContent = `${result.name} was added to the builder catalogue. Add a supplier below if needed.`;
                status.className = 'small text-success';
                createForm.reset();
                createForm.querySelector('input[name="_token"]').value =
                    document.querySelector('meta[name="csrf-token"]')?.content || createForm.querySelector('input[name="_token"]').value;
                selectPart(result, false);
                supplierPart = result;
                document.getElementById('component-supplier-part-name').textContent = result.name;
                supplierStatus.textContent = '';
                supplierStatus.className = 'small';
                supplierPanel.classList.remove('d-none');
                document.getElementById('component-search-form').classList.add('d-none');
                searchStatus.classList.add('d-none');
                searchResults.classList.add('d-none');
                document.querySelector('#component-picker .d-flex.justify-content-between.align-items-center.mt-3').classList.add('d-none');
                await searchParts();
            } catch (error) {
                status.textContent = error.message || 'The part could not be added. Try again.';
                status.className = 'small text-danger';
            } finally {
                submitButton.disabled = false;
            }
        });

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
            build.querySelector('.build-template-select').value = '';
            updateBuildTemplateGuidance(build);
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
            if (event.target.matches('.build-template-select')) {
                updateBuildTemplateGuidance(event.target.closest('.pc-build'));
                return;
            }

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
        form.querySelector('[name="discount_amount"]').addEventListener('input', updateEstimate);

        setNames();
        updateCompatibility();
        updateEstimate();
    })();
</script>
@endpush
@endsection
