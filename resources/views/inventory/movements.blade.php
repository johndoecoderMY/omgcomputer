@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory" title="Stock movements" :back-url="route('inventory.index')" back-label="Back to inventory" />
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Item</th><th>Movement</th><th class="text-end">Change</th><th class="text-end">Balance</th><th>Reference / note</th><th>User</th></tr></thead><tbody>
        @forelse ($movements as $movement)<tr><td>{{ $movement->created_at->format('M j, Y g:i A') }}</td><td>{{ $movement->part?->name ?? 'Deleted item' }}</td><td>{{ ucfirst($movement->type) }}</td><td class="text-end {{ $movement->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td class="text-end">{{ $movement->quantity_after }}</td><td>{{ $movement->reference }} {{ $movement->note }}</td><td>{{ $movement->user?->name ?? 'System' }}</td></tr>
        @empty<tr><td colspan="7" class="text-center text-body-secondary py-5">No stock movements recorded.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $movements->links() }}</div></div>
</div>
@endsection