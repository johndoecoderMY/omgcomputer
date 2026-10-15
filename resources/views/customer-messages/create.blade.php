@extends('layouts.app')

@section('title', 'New Message Template')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customer messages" title="New template" :back-url="route('customer-messages.index')" back-label="All templates" />
    @include('customer-messages._form', ['action' => route('customer-messages.store'), 'method' => 'POST', 'buttonLabel' => 'Create template', 'cancelUrl' => route('customer-messages.index')])
</div>
@endsection