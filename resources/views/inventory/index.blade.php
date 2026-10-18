@extends('layouts.app')

@section('title', 'Parts & Inventory')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" title="Spare parts">
        <x-slot name="actions"><a class="btn btn-primary" href="{{ route('inventory.parts.create') }}"><span class="fas fa-plus me-2"></span>Add item</a><a class="btn btn-outline-primary" href="{{ route('inventory.suppliers.index') }}">Suppliers</a><a class="btn btn-outline-primary" href="{{ route('inventory.parts', ['low_stock' => 1]) }}">Low stock</a><a class="btn btn-outline-primary" href="{{ route('inventory.movements') }}"><span class="fas fa-right-left me-2"></span>Stock movements</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="row g-3 mb-3"><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">Items tracked</div><div class="fs-4 fw-semibold">{{ $partCount }}</div></div></div></div><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">At or below reorder level</div><div class="fs-4 fw-semibold text-danger">{{ $lowStockCount }}</div></div></div></div></div>
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
            data-source="{{ route('inventory.parts.datatable', request()->query()) }}"
            data-columns="{{ json_encode([
                ['data' => 'name', 'name' => 'inventory_parts.name'],
                ['data' => 'sku', 'name' => 'inventory_parts.sku'],
                ['data' => 'supplier_name', 'name' => 'suppliers.name'],
                ['data' => 'stock_quantity', 'name' => 'inventory_parts.stock_quantity'],
                ['data' => 'reorder_level', 'name' => 'inventory_parts.reorder_level'],
                ['data' => 'cost_price', 'name' => 'inventory_parts.cost_price'],
                ['data' => 'selling_price', 'name' => 'inventory_parts.selling_price'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Item</th><th>SKU</th><th>Supplier</th><th>On hand</th><th>Reorder level</th><th>Cost</th><th>Sale price</th><th>Adjust stock</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection