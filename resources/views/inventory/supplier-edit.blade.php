@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory" title="Edit supplier"
        :back-url="route('inventory.suppliers.index')" back-label="All suppliers" />
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form class="card" method="POST" action="{{ route('inventory.suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="supplier-name">Supplier name</label><input class="form-control" id="supplier-name" name="name" value="{{ old('name', $supplier->name) }}" required maxlength="255"></div>
            <div class="col-md-6"><label class="form-label" for="supplier-contact">Contact person</label><input class="form-control" id="supplier-contact" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" maxlength="255"></div>
            <div class="col-md-6"><label class="form-label" for="supplier-email">Email</label><input class="form-control" id="supplier-email" name="email" type="email" value="{{ old('email', $supplier->email) }}" maxlength="255"></div>
            <div class="col-md-6"><label class="form-label" for="supplier-phone">Phone</label><input class="form-control" id="supplier-phone" name="phone" value="{{ old('phone', $supplier->phone) }}" maxlength="50"></div>
            <div class="col-12"><label class="form-label" for="supplier-website">Website</label><input class="form-control" id="supplier-website" name="website" type="url" value="{{ old('website', $supplier->website) }}" maxlength="255"></div>
            <div class="col-12"><label class="form-label" for="supplier-notes">Notes</label><textarea class="form-control" id="supplier-notes" name="notes" rows="3" maxlength="2000">{{ old('notes', $supplier->notes) }}</textarea></div>
        </div>
        <div class="card-footer bg-body d-flex justify-content-between"><a class="btn btn-outline-secondary" href="{{ route('inventory.suppliers.index') }}">Cancel</a><button class="btn btn-primary">Save supplier</button></div>
    </form>
    <form class="mt-3" method="POST" action="{{ route('inventory.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier? Linked parts will remain without a supplier.');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete supplier</button></form>
</div>
@endsection
