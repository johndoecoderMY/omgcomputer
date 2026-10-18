@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory / Suppliers" :title="$supplier->name" :back-url="route('inventory.suppliers.index')" back-label="All suppliers">
        <x-slot:actions><a class="btn btn-outline-primary" href="{{ route('inventory.suppliers.edit', $supplier) }}">Edit</a><a class="btn btn-outline-danger" href="{{ route('inventory.suppliers.delete', $supplier) }}">Delete</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-3"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">Contact person</dt><dd class="col-sm-9">{{ $supplier->contact_name ?: '—' }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $supplier->email ?: '—' }}</dd>
        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $supplier->phone ?: '—' }}</dd>
        <dt class="col-sm-3">Website</dt><dd class="col-sm-9">@if ($supplier->website)<a href="{{ $supplier->website }}" target="_blank" rel="noopener noreferrer">{{ $supplier->website }}</a>@else—@endif</dd>
        <dt class="col-sm-3">Notes</dt><dd class="col-sm-9">{{ $supplier->notes ?: '—' }}</dd>
    </dl></div></div>
    <div class="card"><div class="card-header fw-semibold">Linked inventory parts ({{ $supplier->parts->count() }})</div>
        <ul class="list-group list-group-flush">@forelse ($supplier->parts as $part)<li class="list-group-item d-flex justify-content-between"><span>{{ $part->name }}</span><a href="{{ route('inventory.parts.show', $part) }}">View part</a></li>@empty<li class="list-group-item text-body-secondary">No inventory parts linked to this supplier.</li>@endforelse</ul>
    </div>
</div>
@endsection
