@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory" title="Stock movements" :back-url="route('inventory.index')" back-label="Back to inventory" />
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
            data-source="{{ route('inventory.movements.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'created_at', 'name' => 'inventory_movements.created_at'],
                ['data' => 'part_name', 'name' => 'inventory_parts.name'],
                ['data' => 'type', 'name' => 'inventory_movements.type'],
                ['data' => 'quantity_change', 'name' => 'inventory_movements.quantity_change'],
                ['data' => 'quantity_after', 'name' => 'inventory_movements.quantity_after'],
                ['data' => 'reference', 'name' => 'inventory_movements.reference'],
                ['data' => 'note', 'name' => 'inventory_movements.note'],
                ['data' => 'user_name', 'name' => 'users.name'],
            ]) }}">
            <thead><tr><th>Date</th><th>Item</th><th>Movement</th><th>Change</th><th>Balance</th><th>Reference</th><th>Note</th><th>User</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection