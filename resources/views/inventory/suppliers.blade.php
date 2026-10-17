@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory" title="Suppliers" description="Maintain supplier contacts and link them to stocked parts.">
        <x-slot:actions><a class="btn btn-outline-primary" href="{{ route('inventory.parts') }}">Back to parts</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <details class="card mb-3" open>
        <summary class="card-header fw-semibold">Add supplier</summary>
        <form method="POST" action="{{ route('inventory.suppliers.store') }}">
            @csrf
            <div class="card-body row g-3">
                <div class="col-md-4"><label class="form-label" for="supplier-name">Supplier name</label><input class="form-control" id="supplier-name" name="name" value="{{ old('name') }}" required maxlength="255"></div>
                <div class="col-md-4"><label class="form-label" for="supplier-contact">Contact person</label><input class="form-control" id="supplier-contact" name="contact_name" maxlength="255"></div>
                <div class="col-md-4"><label class="form-label" for="supplier-email">Email</label><input class="form-control" id="supplier-email" name="email" type="email" maxlength="255"></div>
                <div class="col-md-4"><label class="form-label" for="supplier-phone">Phone</label><input class="form-control" id="supplier-phone" name="phone" maxlength="50"></div>
                <div class="col-md-8"><label class="form-label" for="supplier-website">Website</label><input class="form-control" id="supplier-website" name="website" type="url" maxlength="255"></div>
                <div class="col-12"><label class="form-label" for="supplier-notes">Notes</label><textarea class="form-control" id="supplier-notes" name="notes" rows="2" maxlength="2000"></textarea></div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">Add supplier</button></div>
        </form>
    </details>

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
