@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customers" title="Edit {{ $customer->name }}" :back-url="route('customers.show', $customer)" back-label="Back to profile" />
    @include('customers._form', ['action' => route('customers.update', $customer), 'method' => 'PUT', 'buttonLabel' => 'Save changes', 'cancelUrl' => route('customers.show', $customer)])
</div>
@endsection