@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory" title="Suppliers" description="Maintain supplier contacts and link them to stocked parts.">
        <x-slot:actions><a class="btn btn-primary" href="{{ route('inventory.suppliers.create') }}">Add supplier</a><a class="btn btn-outline-primary" href="{{ route('inventory.parts') }}">Back to parts</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
            data-source="{{ route('inventory.suppliers.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'name', 'name' => 'suppliers.name'],
                ['data' => 'contact_name', 'name' => 'suppliers.contact_name'],
                ['data' => 'email', 'name' => 'suppliers.email'],
                ['data' => 'phone', 'name' => 'suppliers.phone'],
                ['data' => 'parts_count', 'name' => 'parts_count'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Supplier</th><th>Contact</th><th>Email</th><th>Phone</th><th>Parts</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
