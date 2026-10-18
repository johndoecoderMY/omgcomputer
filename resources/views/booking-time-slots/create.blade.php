@extends('layouts.app')

@section('title', 'Add Available Time')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Bookings" title="Add available time" :back-url="route('booking-time-slots.index')" back-label="All available times" />
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('booking-time-slots.store') }}">
        @csrf
        @include('booking-time-slots._form', ['days' => \App\Models\BookingTimeSlot::DAYS])
        <div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('booking-time-slots.index') }}">Cancel</a><button class="btn btn-primary">Create available time</button></div>
    </form>
</div>
@endsection
