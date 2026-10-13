@extends('layouts.app')

@section('title', 'Inventory Overview')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Operations" title="Inventory overview" description="Quick stock position and recent activity." :breadcrumbs="[['label' => 'Inventory']]">
            <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('inventory.parts') }}"><span class="fas fa-boxes me-2"></span>Manage spare parts</a>
            </x-slot:actions>
        </x-page-header>
        <nav class="nav nav-tabs mb-4">
            <a class="nav-link active" href="{{ route('inventory.index') }}">Overview</a>
            <a class="nav-link" href="{{ route('inventory.parts') }}">Spare parts</a>
            <a class="nav-link" href="{{ route('inventory.movements') }}">Stock movements</a>
        </nav>
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-4"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Active parts</p><p class="h3 mb-0">{{ $partCount }}</p></div></div></div>
            <div class="col-6 col-xl-4"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Need reorder</p><p class="h3 {{ $lowStockCount ? 'text-warning' : 'text-success' }} mb-0">{{ $lowStockCount }}</p></div></div></div>
            <div class="col-12 col-xl-4"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Stock value at cost</p><p class="h3 mb-0">RM{{ number_format($stockValue, 2) }}</p></div></div></div>
        </div>
        <section class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Recent stock movements</h2><a href="{{ route('inventory.movements') }}">View all</a></div>
            <div class="table-responsive"><table class="table table-striped align-middle mb-0">
                <thead><tr><th>Date</th><th>Part</th><th>Movement</th><th>Change</th><th>Recorded by</th></tr></thead>
                <tbody>@forelse ($recentMovements as $movement)
                    <tr><td>{{ $movement->created_at->format('M j, Y H:i') }}</td><td>{{ $movement->part->sku }} — {{ $movement->part->name }}</td><td>{{ ucfirst($movement->type) }}</td><td class="{{ $movement->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td>{{ $movement->user->name }}</td></tr>
                @empty<tr><td colspan="5" class="text-center text-600 py-4">No stock movements recorded yet.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    </div>
@endsection
