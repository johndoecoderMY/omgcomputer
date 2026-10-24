@extends('layouts.app')

@section('title', 'Parts')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" title="Parts">
        <x-slot name="actions"><a class="btn btn-primary" href="{{ route('inventory.parts.create') }}"><span class="bi bi-plus me-2"></span>Add item</a><a class="btn btn-outline-primary" href="{{ route('inventory.suppliers.index') }}">Suppliers</a><a class="btn btn-outline-primary" href="{{ route('inventory.movements') }}"><span class="bi bi-arrow-left-right me-2"></span>Stock movements</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="row g-3 mb-3"><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">Items tracked</div><div class="fs-4 fw-semibold">{{ $partCount }}</div></div></div></div><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">At or below reorder level</div><div class="fs-4 fw-semibold text-danger">{{ $lowStockCount }}</div></div></div></div></div>
    @php
        $partFilters = array_merge(
            request()->only(['name', 'sku', 'type', 'low_stock']),
            is_array(request('filter')) ? request('filter') : [],
        );
        $activePartFilters = collect(['name', 'sku', 'type', 'low_stock'])
            ->filter(fn (string $filter): bool => filled($partFilters[$filter] ?? null))
            ->count();
    @endphp
    <div class="card p-2">
        <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#parts-filters-modal">
                <span class="bi bi-filter me-2" aria-hidden="true"></span>Filters
                @if ($activePartFilters > 0)
                    <span class="badge text-bg-primary ms-1">{{ $activePartFilters }}</span>
                @endif
            </button>
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('inventory.parts.datatable', ['filter' => $partFilters]) }}"
            data-columns="{{ json_encode([
                ['data' => 'name', 'name' => 'inventory_parts.name'],
                ['data' => 'sku', 'name' => 'inventory_parts.sku'],
                ['data' => 'type_label', 'orderable' => false, 'searchable' => false],
                ['data' => 'supplier_name', 'name' => 'suppliers.name'],
                ['data' => 'stock_quantity', 'name' => 'inventory_parts.stock_quantity'],
                ['data' => 'reorder_level', 'name' => 'inventory_parts.reorder_level'],
                ['data' => 'cost_price', 'name' => 'inventory_parts.cost_price'],
                ['data' => 'selling_price', 'name' => 'inventory_parts.selling_price'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Item</th><th>SKU</th><th>Type</th><th>Supplier</th><th>On hand</th><th>Reorder level</th><th>Cost</th><th>Sale price</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
    <div class="modal fade" id="parts-filters-modal" tabindex="-1" aria-labelledby="parts-filters-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('inventory.parts') }}">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="parts-filters-title">Filter parts</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="parts-filter-name">Item name</label>
                        <input class="form-control" id="parts-filter-name" name="filter[name]" value="{{ $partFilters['name'] ?? '' }}" placeholder="Search item name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="parts-filter-sku">SKU</label>
                        <input class="form-control" id="parts-filter-sku" name="filter[sku]" value="{{ $partFilters['sku'] ?? '' }}" placeholder="Search SKU">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="parts-filter-type">Item type</label>
                        <select class="form-select" id="parts-filter-type" name="filter[type]">
                            <option value="">All types</option>
                            @foreach (\App\Models\InventoryPart::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(($partFilters['type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="parts-filter-low-stock">Stock level</label>
                        <select class="form-select" id="parts-filter-low-stock" name="filter[low_stock]">
                            <option value="">All stock levels</option>
                            <option value="1" @selected(($partFilters['low_stock'] ?? '') === '1')>At or below reorder level</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.parts') }}">Reset</a>
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection