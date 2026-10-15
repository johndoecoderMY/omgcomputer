@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customers" title="New customer profile" :back-url="route('customers.index')" back-label="Back to customers" />
    @include('customers._form', ['action' => route('customers.store'), 'method' => 'POST', 'buttonLabel' => 'Create profile', 'cancelUrl' => route('customers.index')])
</div>
@endsection