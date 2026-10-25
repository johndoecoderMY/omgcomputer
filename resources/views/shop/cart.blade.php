@extends('layouts.app')

@section('title', 'Shop cart')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" title="Shopping cart" description="Review quantities before creating a customer order.">
        <x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('shop.preview.products.grid') }}"><i class="bi bi-arrow-left me-2"></i>Continue shopping</a></x-slot>
    </x-page-header>
    @if ($cart->isEmpty())
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-cart3 text-body-secondary bi-2x mb-3" aria-hidden="true"></i>
            <h2 class="h5">Your cart is empty</h2>
            <p class="text-body-secondary">Browse the product grid to add available items.</p>
            <a class="btn btn-primary" href="{{ route('shop.preview.products.grid') }}">Browse products</a>
        </div></div>
    @else
        <form class="card" method="POST" action="{{ route('shop.cart.update') }}">
            @csrf
            @method('PUT')
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Product</th><th class="text-end">Unit price</th><th style="width: 9rem">Quantity</th><th class="text-end">Line total</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($cart as $id => $row)
                            <tr>
                                <td>
                                    <a class="fw-semibold text-decoration-none" href="{{ route('shop.preview.products.show', $row['product']) }}">{{ $row['product']->name }}</a>
                                    <div class="small text-body-secondary">{{ $row['product']->sku ?: 'No SKU' }} · {{ $row['available'] }} available</div>
                                </td>
                                <td class="text-end text-nowrap">RM {{ number_format($row['price'], 2) }}</td>
                                <td>
                                    <label class="visually-hidden" for="quantity-{{ $id }}">Quantity for {{ $row['product']->name }}</label>
                                    <input class="form-control" id="quantity-{{ $id }}" name="quantities[{{ $id }}]" type="number" min="0" max="{{ $row['available'] }}" value="{{ $row['quantity'] }}">
                                </td>
                                <td class="text-end fw-semibold text-nowrap">RM {{ number_format($row['price'] * $row['quantity'], 2) }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" form="remove-{{ $id }}" aria-label="Remove {{ $row['product']->name }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end text-nowrap">RM {{ number_format($subtotal, 2) }}</th><th></th></tr></tfoot>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                <button class="btn btn-outline-primary" type="submit">Update cart</button>
                <a class="btn btn-primary" href="{{ route('shop.checkout') }}">Continue to checkout <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
            </div>
        </form>
        @foreach ($cart as $id => $row)
            <form class="d-none" id="remove-{{ $id }}" method="POST" action="{{ route('shop.cart.remove', $id) }}">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
</div>
@endsection
