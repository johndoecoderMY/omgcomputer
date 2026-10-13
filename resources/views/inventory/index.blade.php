@extends('layouts.app')

@section('title', 'Spare Parts')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Operations" title="Spare parts" description="Manage parts and spot reorder needs early." :breadcrumbs="[['label' => 'Inventory overview', 'url' => route('inventory.index')], ['label' => 'Spare parts']]">
            <x-slot:actions>
            <div class="d-flex gap-2"><a href="{{ route('reports.index') }}" class="btn btn-falcon-default"><span class="fas fa-chart-line me-2"></span>View reports</a><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createPartModal"><span class="fas fa-plus me-2"></span>Add part</button></div>
            </x-slot:actions>
        </x-page-header>
        <nav class="nav nav-tabs mb-4">
            <a class="nav-link" href="{{ route('inventory.index') }}">Overview</a>
            <a class="nav-link active" href="{{ route('inventory.parts') }}">Spare parts</a>
            <a class="nav-link" href="{{ route('inventory.movements') }}">Stock movements</a>
        </nav>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="card mb-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div><h2 class="h5 mb-1">Spare parts</h2><p class="text-600 fs-10 mb-0">Invoices deduct linked part quantities when issued.</p></div>
                <button class="btn btn-sm btn-falcon-default" type="button" data-bs-toggle="modal" data-bs-target="#inventoryFilterModal"><span class="fas fa-filter me-1"></span>Filters</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" data-datatables='{"pageLength":10,"language":{"emptyTable":"No parts match the current filters."}}'>
                    <thead><tr><th>SKU / part</th><th>Available</th><th>Reorder level</th><th>Unit cost</th><th>Sale price</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach ($parts as $part)
                            <tr>
                                <td><strong>{{ $part->sku }}</strong><div class="text-700">{{ $part->name }}</div>@if($part->description)<small class="text-600">{{ $part->description }}</small>@endif</td>
                                <td><span class="badge badge-subtle-{{ $part->stock_quantity <= $part->reorder_level ? 'warning' : 'success' }}">{{ $part->stock_quantity }}</span></td>
                                <td>{{ $part->reorder_level }}</td>
                                <td>RM{{ number_format((float) $part->unit_cost, 2) }}</td>
                                <td>RM{{ number_format((float) $part->sale_price, 2) }}</td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-falcon-default" type="button" data-bs-toggle="modal" data-bs-target="#stockModal{{ $part->id }}">Stock</button>
                                    <button class="btn btn-sm btn-falcon-default" type="button" data-bs-toggle="modal" data-bs-target="#editPartModal{{ $part->id }}">Edit</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @foreach ($parts as $part)
            <div class="modal fade" id="stockModal{{ $part->id }}" tabindex="-1" aria-labelledby="stockModalLabel{{ $part->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <form method="POST" action="{{ route('inventory.movements.store', $part) }}">
                    @csrf
                    <input type="hidden" name="ui_modal" value="stockModal{{ $part->id }}">
                    <div class="modal-header"><h2 class="modal-title fs-5" id="stockModalLabel{{ $part->id }}">Update stock · {{ $part->name }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body"><div class="mb-3"><label class="form-label" for="move-type-{{ $part->id }}">Movement</label><select class="form-select" id="move-type-{{ $part->id }}" name="type" required><option value="received">Receive stock</option><option value="used">Use / remove stock</option><option value="counted">Set physical count</option></select></div><div class="mb-3"><label class="form-label" for="move-quantity-{{ $part->id }}">Quantity / physical count</label><input class="form-control" id="move-quantity-{{ $part->id }}" name="quantity" type="number" min="0" value="1" required></div><div><label class="form-label" for="move-note-{{ $part->id }}">Note</label><input class="form-control" id="move-note-{{ $part->id }}" name="note" maxlength="500"></div></div>
                    <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Record movement</button></div>
                </form>
            </div></div></div>
            <div class="modal fade" id="editPartModal{{ $part->id }}" tabindex="-1" aria-labelledby="editPartModalLabel{{ $part->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <form method="POST" action="{{ route('inventory.update', $part) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="ui_modal" value="editPartModal{{ $part->id }}">
                    <div class="modal-header"><h2 class="modal-title fs-5" id="editPartModalLabel{{ $part->id }}">Edit {{ $part->name }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label" for="edit-sku-{{ $part->id }}">SKU</label><input class="form-control" id="edit-sku-{{ $part->id }}" name="sku" value="{{ $part->sku }}" required></div><div class="col-md-6"><label class="form-label" for="edit-name-{{ $part->id }}">Name</label><input class="form-control" id="edit-name-{{ $part->id }}" name="name" value="{{ $part->name }}" required></div><div class="col-12"><label class="form-label" for="edit-description-{{ $part->id }}">Description</label><input class="form-control" id="edit-description-{{ $part->id }}" name="description" value="{{ $part->description }}"></div><div class="col-md-4"><label class="form-label" for="edit-reorder-{{ $part->id }}">Reorder level</label><input class="form-control" id="edit-reorder-{{ $part->id }}" name="reorder_level" type="number" min="0" value="{{ $part->reorder_level }}" required></div><div class="col-md-4"><label class="form-label" for="edit-cost-{{ $part->id }}">Unit cost (RM)</label><input class="form-control" id="edit-cost-{{ $part->id }}" name="unit_cost" type="number" min="0" step="0.01" value="{{ $part->unit_cost }}" required></div><div class="col-md-4"><label class="form-label" for="edit-price-{{ $part->id }}">Sale price (RM)</label><input class="form-control" id="edit-price-{{ $part->id }}" name="sale_price" type="number" min="0" step="0.01" value="{{ $part->sale_price }}" required></div></div></div>
                    <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save part</button></div>
                </form>
            </div></div></div>
        @endforeach

    </div>
    <div class="modal fade" id="createPartModal" tabindex="-1" aria-labelledby="createPartModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            <input type="hidden" name="ui_modal" value="createPartModal">
            <div class="modal-header"><h2 class="modal-title fs-5" id="createPartModalLabel">Add inventory part</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><p class="text-600 fs-10">Opening quantities are recorded in the stock movement history.</p><div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="part-sku">SKU</label><input class="form-control" id="part-sku" name="sku" value="{{ old('sku') }}" maxlength="80" required></div><div class="col-md-6"><label class="form-label" for="part-name">Part name</label><input class="form-control" id="part-name" name="name" value="{{ old('name') }}" maxlength="180" required></div>
                <div class="col-md-4"><label class="form-label" for="part-opening">Opening stock</label><input class="form-control" id="part-opening" name="opening_stock" type="number" min="0" value="{{ old('opening_stock', 0) }}" required></div><div class="col-md-4"><label class="form-label" for="part-reorder">Reorder at</label><input class="form-control" id="part-reorder" name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 2) }}" required></div><div class="col-md-4"><label class="form-label" for="part-cost">Cost (RM)</label><input class="form-control" id="part-cost" name="unit_cost" type="number" min="0" step="0.01" value="{{ old('unit_cost', '0.00') }}" required></div><div class="col-md-4"><label class="form-label" for="part-price">Sale price (RM)</label><input class="form-control" id="part-price" name="sale_price" type="number" min="0" step="0.01" value="{{ old('sale_price', '0.00') }}" required></div><div class="col-md-8"><label class="form-label" for="part-description">Description</label><input class="form-control" id="part-description" name="description" value="{{ old('description') }}" maxlength="3000"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit"><span class="fas fa-plus me-2"></span>Add part</button></div>
        </form>
    </div></div></div>
    <div class="modal fade" id="inventoryFilterModal" tabindex="-1" aria-labelledby="inventoryFilterModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="GET" action="{{ route('inventory.parts') }}">
            <div class="modal-header"><h2 class="modal-title fs-5" id="inventoryFilterModalLabel">Filter inventory</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><div class="mb-3"><label class="form-label" for="inventory-search">Search SKU or name</label><input class="form-control" id="inventory-search" name="search" value="{{ $search }}"></div><div class="form-check"><input class="form-check-input" id="inventory-low-stock" type="checkbox" name="low_stock" value="1" @checked($lowOnly)><label class="form-check-label" for="inventory-low-stock">Show low stock only</label></div></div>
            <div class="modal-footer"><a class="btn btn-falcon-default me-auto" href="{{ route('inventory.parts') }}">Clear filters</a><button class="btn btn-primary" type="submit">Apply filters</button></div>
        </form>
    </div></div></div>
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>
    @endpush
@endsection
