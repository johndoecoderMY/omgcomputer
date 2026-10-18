@extends('layouts.app')

@section('title', 'Available Booking Times')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Bookings" title="Available booking times" description="Set the recurring weekly time slots customers can request.">
        <x-slot:actions><a class="btn btn-primary" href="{{ route('booking-time-slots.create') }}"><span class="fas fa-plus me-2"></span>Add available time</a></x-slot:actions>
    </x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card p-2"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-datatable
            data-source="{{ route('booking-time-slots.datatable') }}"
            data-columns="{{ json_encode([
                ['data' => 'day_label', 'name' => 'day_of_week'],
                ['data' => 'start_time', 'name' => 'start_time'],
                ['data' => 'actions', 'orderable' => false, 'searchable' => false],
            ]) }}">
            <thead><tr><th>Day</th><th>Start time (MYT)</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
@endsection
