@extends('layouts.app')

@section('title', 'Edit Available Time')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Bookings" title="Edit available time" :back-url="route('booking-time-slots.show', $slot)" back-label="Back to time" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('booking-time-slots.update', $slot) }}">
        @csrf
        @method('PUT')
        @include('booking-time-slots._form', ['days' => $days])
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('booking-time-slots.show', $slot) }}">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</div>
@endsection
