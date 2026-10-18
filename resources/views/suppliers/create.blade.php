@extends('layouts.app')

@section('title', 'Add Supplier')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory" title="Add supplier" :back-url="route('inventory.suppliers.index')" back-label="All suppliers" />
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('inventory.suppliers.store') }}">
        @csrf
        @include('suppliers._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('inventory.suppliers.index') }}">Cancel</a><button class="btn btn-primary">Create supplier</button></div>
    </form>
</div>
@endsection
