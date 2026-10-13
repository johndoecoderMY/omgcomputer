@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Operations" title="Stock movements" description="A history of received, used, and counted inventory." :breadcrumbs="[['label' => 'Inventory overview', 'url' => route('inventory.index')], ['label' => 'Stock movements']]">
            <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('inventory.parts') }}"><span class="fas fa-boxes me-2"></span>Manage spare parts</a>
            </x-slot:actions>
        </x-page-header>
        <nav class="nav nav-tabs mb-4">
            <a class="nav-link" href="{{ route('inventory.index') }}">Overview</a>
            <a class="nav-link" href="{{ route('inventory.parts') }}">Spare parts</a>
            <a class="nav-link active" href="{{ route('inventory.movements') }}">Stock movements</a>
        </nav>
        @if (session('status'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="card">
            <div class="table-responsive"><table class="table table-striped align-middle mb-0">
                <thead><tr><th>Date</th><th>Part</th><th>Movement</th><th>Change</th><th>After</th><th>Recorded by</th><th>Note / reference</th></tr></thead>
                <tbody>@foreach ($movements as $movement)
                    <tr><td>{{ $movement->created_at->format('M j, Y H:i') }}</td><td>{{ $movement->part->sku }} — {{ $movement->part->name }}</td><td>{{ ucfirst($movement->type) }}</td><td class="{{ $movement->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td>{{ $movement->quantity_after }}</td><td>{{ $movement->user->name }}</td><td>{{ $movement->note ?? $movement->reference ?? '—' }}</td></tr>
                @endforeach</tbody>
            </table></div>
            @if ($movements->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="text-600 fs-10">Showing {{ $movements->firstItem() }}–{{ $movements->lastItem() }} of {{ $movements->total() }} movements · Page {{ $movements->currentPage() }} of {{ $movements->lastPage() }}</span>
                    <div class="btn-group">
                        @if ($movements->onFirstPage())<span class="btn btn-sm btn-falcon-default disabled">Previous</span>@else<a class="btn btn-sm btn-falcon-default" href="{{ $movements->previousPageUrl() }}">Previous</a>@endif
                        @if ($movements->hasMorePages())<a class="btn btn-sm btn-falcon-default" href="{{ $movements->nextPageUrl() }}">Next</a>@else<span class="btn btn-sm btn-falcon-default disabled">Next</span>@endif
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
