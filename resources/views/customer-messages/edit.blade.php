@extends('layouts.app')

@section('title', 'Edit Message Template')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Customer messages" title="Edit {{ $template->name }}" :back-url="route('customer-messages.show', $template)" back-label="Back to preview" />
    @include('customer-messages._form', ['action' => route('customer-messages.update', $template), 'method' => 'PUT', 'buttonLabel' => 'Save changes', 'cancelUrl' => route('customer-messages.show', $template)])
</div>
@endsection