@extends('layouts.app')

@section('title', 'Products & Services')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Catalogue" title="Products & services" description="Manage services and product prices for bookings and billing.">
        <x-slot name="actions"><a class="btn btn-primary" href="{{ route('products.create') }}"><span class="bi bi-plus me-2"></span>Add product or service</a></x-slot>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info" role="note">Prices are planning estimates for Kampar. Confirm scope, parts, software licences and final charges before quoting.</div>

    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-5"><label class="visually-hidden" for="search">Search catalogue</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search offers, scope or audience"></div>
        <div class="col-md-4"><label class="visually-hidden" for="category">Category</label><select class="form-select" id="category" name="category"><option value="">All categories</option>@foreach ($categories as $key => $label)<option value="{{ $key }}" @selected($selectedCategory === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-outline-primary flex-grow-1">Filter</button><a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Reset</a></div>
    </form>

    <div class="card p-2">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered">
            <thead><tr><th>Offer</th><th>Category</th><th>Availability</th><th>Typical scope</th><th>Target customer</th><th class="text-end">Plan estimate</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="fw-semibold"><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                        <td>{{ $categories[$product->category] }}</td>
                        <td><span class="badge {{ $product->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $product->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td>{{ $product->scope }}</td>
                        <td>{{ $product->audience }}</td>
                        <td class="text-end text-nowrap">{{ $product->price }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-5">No offers match these filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="card-footer text-body-secondary small">{{ $products->count() }} {{ $products->count() === 1 ? 'offer' : 'offers' }}</div>
    </div>
</div>
@endsection