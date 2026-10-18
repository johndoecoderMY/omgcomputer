@extends('layouts.app')

@section('title', 'Available Booking Time')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Bookings / Available times" :title="$dayLabel.' at '.date('g:i A', strtotime($slot->start_time))" :back-url="route('booking-time-slots.index')" back-label="All available times">
        <x-slot:actions><a class="btn btn-outline-primary" href="{{ route('booking-time-slots.edit', $slot) }}">Edit</a><a class="btn btn-outline-danger" href="{{ route('booking-time-slots.delete', $slot) }}">Delete</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body"><dl class="row mb-0"><dt class="col-sm-3">Day</dt><dd class="col-sm-9">{{ $dayLabel }}</dd><dt class="col-sm-3">Start time</dt><dd class="col-sm-9">{{ date('g:i A', strtotime($slot->start_time)) }} MYT</dd></dl></div></div>
</div>
@endsection
