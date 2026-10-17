@extends('layouts.app')

@section('title', 'Create member')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration / Members" title="Create member"
        description="New members receive the default member role and must change their password at first sign-in."
        :back-url="route('admin.users.index')" back-label="All members" />

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card" method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="{{ old('phone') }}" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="team_id">Team <span class="text-body-secondary">(optional)</span></label>
                <select class="form-select" id="team_id" name="team_id">
                    <option value="">No team assigned</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) old('team_id') === (string) $team->id)>
                            {{ $team->department?->name ? $team->department->name.' · ' : '' }}{{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" maxlength="2000">{{ old('address') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">Temporary password</label>
                <input class="form-control" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <div class="form-text">Use at least 12 characters. The member must replace this at first sign-in.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirm temporary password</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
            </div>
        </div>
        <div class="card-footer bg-body d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Create member</button>
        </div>
    </form>
</div>
@endsection
