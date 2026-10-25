@extends('layouts.app')

@section('title', 'Shop checkout')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" title="Checkout" description="Confirm your delivery details and continue to secure CHiP payment.">
        <x-slot name="actions"><a class="btn btn-outline-secondary" href="{{ route('shop.cart') }}"><i class="bi bi-arrow-left me-2"></i>Back to cart</a></x-slot>
    </x-page-header>
    @unless ($chipEnabled)
        <div class="alert alert-warning" role="status">
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
            CHiP checkout is not configured. Add sandbox credentials to enable order placement.
        </div>
    @endunless
    <form class="row g-3" method="POST" action="{{ route('shop.checkout.place') }}">
        @csrf
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header fw-semibold">Customer and delivery</div>
                <div class="card-body">
                    @error('customer')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror
                    <div class="mb-3">
                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-body-secondary">{{ auth()->user()->email }}</div>
                        <div class="form-text">CHiP will send the payment link to your account email.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="customer_address">Delivery address <span class="text-body-secondary">(optional)</span></label>
                        <textarea class="form-control @error('customer_address') is-invalid @enderror" id="customer_address" name="customer_address" rows="3" maxlength="3000">{{ old('customer_address', $customer?->address ?? auth()->user()->address) }}</textarea>
                        @error('customer_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="notes">Order notes <span class="text-body-secondary">(optional)</span></label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Order summary</div>
                <ul class="list-group list-group-flush">
                    @foreach ($cart as $row)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span>{{ $row['product']->name }} <span class="text-body-secondary">× {{ $row['quantity'] }}</span></span>
                            <span class="text-nowrap">RM {{ number_format($row['price'] * $row['quantity'], 2) }}</span>
                        </li>
                    @endforeach
                    <li class="list-group-item d-flex justify-content-between fw-bold">
                        <span>Total</span><span>RM {{ number_format($subtotal, 2) }}</span>
                    </li>
                </ul>
                <div class="card-body">
                    <p class="small text-body-secondary">Stock is reserved when the order is created. Unpaid orders release their reservation if CHiP reports a failed or cancelled payment.</p>
                    <button class="btn btn-primary w-100" type="submit" @disabled(! $chipEnabled)>
                        <i class="bi bi-lock me-2" aria-hidden="true"></i>Continue to CHiP sandbox
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
