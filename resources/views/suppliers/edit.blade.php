@extends('layouts.app')

@section('title', 'Edit '.$supplier->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Inventory / Suppliers" :title="'Edit '.$supplier->name" :back-url="route('inventory.suppliers.show', $supplier)" back-label="Back to supplier" />
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('inventory.suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        @include('suppliers._form')
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('inventory.suppliers.show', $supplier) }}">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</div>
@endsection
