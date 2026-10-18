@extends('layouts.app')

@section('title', 'Delete '.$supplier->name)

@section('content')
<div class="container py-3">
    <x-page-header eyebrow="Inventory / Suppliers" title="Delete supplier" :back-url="route('inventory.suppliers.show', $supplier)" back-label="Cancel" />
    <div class="card border-danger"><div class="card-body">
        <p>Delete <strong>{{ $supplier->name }}</strong>? Its {{ $supplier->parts_count }} linked inventory item(s) will remain and become unassigned.</p>
        <form method="POST" action="{{ route('inventory.suppliers.destroy', $supplier) }}" class="d-flex justify-content-end gap-2">
            @csrf
            @method('DELETE')
            <a class="btn btn-outline-secondary" href="{{ route('inventory.suppliers.show', $supplier) }}">Cancel</a>
            <button class="btn btn-danger">Delete supplier</button>
        </form>
    </div></div>
</div>
@endsection
