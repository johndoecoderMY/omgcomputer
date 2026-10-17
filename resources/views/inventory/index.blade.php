@extends('layouts.app')

@section('title', 'Parts & Inventory')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" title="Spare parts">
        <x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('inventory.suppliers.index') }}">Suppliers</a><a class="btn btn-outline-primary" href="{{ route('inventory.parts', ['low_stock' => 1]) }}">Low stock</a><a class="btn btn-outline-primary" href="{{ route('inventory.movements') }}"><span class="fas fa-right-left me-2"></span>Stock movements</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="row g-3 mb-3"><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">Items tracked</div><div class="fs-4 fw-semibold">{{ $partCount }}</div></div></div></div><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">At or below reorder level</div><div class="fs-4 fw-semibold text-danger">{{ $lowStockCount }}</div></div></div></div></div>
    <details class="card mb-3"><summary class="card-header fw-semibold">Add inventory item</summary><form method="POST" action="{{ route('inventory.store') }}"><div class="card-body row g-3">@csrf
        <div class="col-md-4"><label class="form-label" for="name">Item name</label><input class="form-control" id="name" name="name" required></div><div class="col-md-3"><label class="form-label" for="sku">SKU</label><input class="form-control" id="sku" name="sku"></div><div class="col-md-5"><label class="form-label" for="description">Description</label><input class="form-control" id="description" name="description"></div>
        <div class="col-md-4"><label class="form-label" for="supplier_id">Supplier</label><select class="form-select" id="supplier_id" name="supplier_id"><option value="">No supplier linked</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select><a class="small" href="{{ route('inventory.suppliers.index') }}">Manage suppliers</a></div>
        <div class="col-md-3"><label class="form-label" for="cost_price">Unit cost</label><input class="form-control" id="cost_price" name="cost_price" type="number" min="0" step="0.01" value="0" required></div><div class="col-md-3"><label class="form-label" for="selling_price">Sale price</label><input class="form-control" id="selling_price" name="selling_price" type="number" min="0" step="0.01" value="0" required></div><div class="col-md-3"><label class="form-label" for="stock_quantity">Opening stock</label><input class="form-control" id="stock_quantity" name="stock_quantity" type="number" min="0" value="0" required></div><div class="col-md-3"><label class="form-label" for="reorder_level">Reorder at</label><input class="form-control" id="reorder_level" name="reorder_level" type="number" min="0" value="0" required></div>
    </div><div class="card-footer text-end"><button class="btn btn-primary">Add item</button></div></form></details>
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
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