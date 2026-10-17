@extends('layouts.app')

@section('title', $user->name . ' - Profile')

@section('content')
<div class="container-fluid py-3 profile-page">
    <x-page-header eyebrow="Team directory" :title="$user->name"
        description="Member profile and team contact information."
        :back-url="route('teams.index')" back-label="Team directory" />

    <div class="row g-3">
        <aside class="col-12 col-xl-3">
            @include('profile._identity-card', ['isSelf' => false])
        </aside>
        <main class="col-12 col-xl-9">
            <div class="card p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0">Personal information</h2>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Full name</span>
                            <span class="profile-info-value">{{ $user->name }}</span>
                        </div>
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Email</span>
                            <a class="profile-info-value" href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                        </div>
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Phone</span>
                            @if ($user->phone)
                                <a class="profile-info-value" href="tel:{{ $user->phone }}">{{ $user->phone }}</a>
                            @else
                                <span class="profile-info-value text-body-secondary">Not provided</span>
                            @endif
                        </div>
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Team</span>
                            <span class="profile-info-value">{{ $user->teams->pluck('name')->join(', ') ?: 'Not assigned' }}</span>
                        </div>
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Department</span>
                            <span class="profile-info-value">{{ $user->teams->pluck('department.name')->filter()->unique()->join(', ') ?: 'Not assigned' }}</span>
                        </div>
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Member since</span>
                            <span class="profile-info-value">{{ $user->created_at->format('F Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@include('profile._styles')
@endsection
