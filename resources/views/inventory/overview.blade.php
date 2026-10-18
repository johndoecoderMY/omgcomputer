@extends('layouts.app')

@section('title', 'Inventory Overview')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operations" title="Inventory overview">
        <x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('inventory.suppliers.index') }}">Suppliers</a><a class="btn btn-outline-primary" href="{{ route('inventory.parts') }}">Spare parts</a><a class="btn btn-outline-primary" href="{{ route('inventory.movements') }}">All stock movements</a></x-slot>
    </x-page-header>
    <div class="row g-3 mb-3"><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">Items tracked</div><div class="fs-4 fw-semibold">{{ $partCount }}</div></div></div></div><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">At or below reorder level</div><div class="fs-4 fw-semibold text-danger">{{ $lowStockCount }}</div><a class="small" href="{{ route('inventory.parts', ['low_stock' => 1]) }}">Review low stock</a></div></div></div><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">Suppliers</div><div class="fs-4 fw-semibold">{{ $supplierCount }}</div><a class="small" href="{{ route('inventory.suppliers.index') }}">Manage suppliers</a></div></div></div><div class="col-md-3"><div class="card p-2"><div class="card-body"><div class="small text-body-secondary">Inventory reporting</div><div class="mt-2"><a href="{{ route('reports.inventory') }}">Open inventory report <span class="fas fa-arrow-right ms-1"></span></a></div></div></div></div></div>
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Recent stock movements</span><a href="{{ route('inventory.movements') }}">View all</a></div><div class="table-responsive"><table class="table align-middle mb-0 table-bordered"><thead><tr><th>Date</th><th>Item</th><th>Movement</th><th class="text-end">Change</th><th class="text-end">Balance</th><th>Reference</th></tr></thead><tbody>
        @forelse ($recentMovements as $movement)<tr><td>{{ $movement->created_at->format('M j, Y') }}</td><td>{{ $movement->part?->name ?? 'Deleted item' }}</td><td>{{ ucfirst($movement->type) }}</td><td class="text-end {{ $movement->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td class="text-end">{{ $movement->quantity_after }}</td><td>{{ $movement->reference ?: '—' }}</td></tr>
        @empty<tr><td colspan="6" class="text-center text-body-secondary py-5">No recent stock movements.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection