@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-3 profile-page">
    <x-page-header eyebrow="Account" title="My profile"
        description="View and update your personal information, account security, and activity." />

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success" role="status">Profile details saved.</div>
    @elseif (session('status') === 'password-updated')
        <div class="alert alert-success" role="status">Password changed.</div>
    @endif
    @if ($errors->hasBag('default') && $errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="row g-3">
        <aside class="col-12 col-xl-3">
            @include('profile._identity-card', ['isSelf' => true])
        </aside>
        <main class="col-12 col-xl-9">
            <div class="card p-2"> 
                <div class="card-body p-0">
                    <div class="profile-tabs px-3" role="tablist" aria-label="Profile sections">
                        <button class="profile-tab active" id="profile-tab-overview" type="button"
                            role="tab" aria-selected="true" aria-controls="profile-pane-overview"
                            data-profile-tab="overview"><span class="bi bi-person me-2" aria-hidden="true"></span>Overview</button>
                        <button class="profile-tab" id="profile-tab-edit" type="button"
                            role="tab" aria-selected="false" aria-controls="profile-pane-edit"
                            data-profile-tab="edit"><span class="bi bi-pencil me-2" aria-hidden="true"></span>Edit profile</button>
                        <button class="profile-tab" id="profile-tab-security" type="button"
                            role="tab" aria-selected="false" aria-controls="profile-pane-security"
                            data-profile-tab="security"><span class="bi bi-shield-shaded me-2" aria-hidden="true"></span>Security</button>
                        <button class="profile-tab" id="profile-tab-activity" type="button"
                            role="tab" aria-selected="false" aria-controls="profile-pane-activity"
                            data-profile-tab="activity"><span class="bi bi-clock-history me-2" aria-hidden="true"></span>Activity</button>
                    </div>

                    <div class="p-3 p-lg-4">
                        <section class="profile-tab-pane active" id="profile-pane-overview"
                            role="tabpanel" aria-labelledby="profile-tab-overview" data-profile-pane="overview">
                            <div class="profile-section">
                                <div class="profile-section-header">
                                    <h2 class="h5 mb-0">About</h2>
                                </div>
                                <p class="mb-0 text-body-secondary">
                                    {{ !$user->hasRole('customer') ? ($user->teams->pluck('description')->filter()->join(' · ') ?: 'Your profile keeps your contact details and account information in one place.') : 'Your profile keeps your contact details and account information in one place.' }}
                                </p>
                            </div>

                            <div class="profile-section">
                                <div class="profile-section-header">
                                    <h2 class="h5 mb-0">Personal information</h2>
                                </div>
                                <div class="row g-3">
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
                                        <span class="profile-info-label">Address</span>
                                        <span class="profile-info-value">{{ $user->address ?: 'Not provided' }}</span>
                                    </div>
                                    @unless ($user->hasRole('customer'))
                                        <div class="col-sm-6 profile-info-item">
                                            <span class="profile-info-label">Team</span>
                                            <span class="profile-info-value">{{ $user->teams->pluck('name')->join(', ') ?: 'Not assigned' }}</span>
                                        </div>
                                        <div class="col-sm-6 profile-info-item">
                                            <span class="profile-info-label">Department</span>
                                            <span class="profile-info-value">{{ $user->teams->pluck('department.name')->filter()->unique()->join(', ') ?: 'Not assigned' }}</span>
                                        </div>
                                    @endunless
                                </div>
                            </div>
                        </section>

                        <section class="profile-tab-pane" id="profile-pane-edit" role="tabpanel"
                            aria-labelledby="profile-tab-edit" data-profile-pane="edit" hidden>
                            <div class="profile-section-header">
                                <h2 class="h5 mb-1">Edit profile</h2>
                                <p class="text-body-secondary mb-0">Update the contact information shown on your profile.</p>
                            </div>
                            @include('profile.partials.update-profile-information-form')
                        </section>

                        <section class="profile-tab-pane" id="profile-pane-security" role="tabpanel"
                            aria-labelledby="profile-tab-security" data-profile-pane="security" hidden>
                            <div class="profile-section">
                                <div class="profile-section-header">
                                    <h2 class="h5 mb-1">Change password</h2>
                                    <p class="text-body-secondary mb-0">Use your current password to set a new one.</p>
                                </div>
                                @include('profile.partials.update-password-form')
                            </div>
                            <div class="profile-section">
                                <div class="profile-section-header">
                                    <h2 class="h5 mb-1 text-danger">Delete account</h2>
                                    <p class="text-body-secondary mb-0">Account deletion is permanent and cannot be undone.</p>
                                </div>
                                @include('profile.partials.delete-user-form')
                            </div>
                        </section>

                        <section class="profile-tab-pane" id="profile-pane-activity" role="tabpanel"
                            aria-labelledby="profile-tab-activity" data-profile-pane="activity" hidden>
                            <div class="profile-section-header">
                                <h2 class="h5 mb-1">Account activity</h2>
                                <p class="text-body-secondary mb-0">Profile updates and password changes are recorded here.</p>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-bordered" data-datatable
                                    data-source="{{ route('profile.activity') }}"
                                    data-columns="{{ json_encode([
                                        ['data' => 'created_at', 'name' => 'created_at'],
                                        ['data' => 'event', 'name' => 'event'],
                                        ['data' => 'changed_fields', 'name' => 'changed_fields', 'orderable' => false],
                                    ]) }}">
                                    <thead><tr><th>Date</th><th>Activity</th><th>Details</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@include('profile._styles')
@endsection

@push('scripts')
<script>
    (() => {
        const tabs = [...document.querySelectorAll('[data-profile-tab]')];
        const panes = [...document.querySelectorAll('[data-profile-pane]')];
        if (!tabs.length || !panes.length) return;

        const activate = (name) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.profileTab === name;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panes.forEach((pane) => {
                pane.hidden = pane.dataset.profilePane !== name;
                pane.classList.toggle('active', !pane.hidden);
            });
            if (name === 'activity') {
                window.jQuery?.fn?.dataTable?.tables({ visible: true, api: true })
                    .columns.adjust();
            }
        };

        tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.profileTab)));

        const requestedTab = window.location.hash.replace('#', '');
        const statusTab = @json(session('profile-tab'));
        const invalidPassword = document.querySelector('#profile-pane-security .is-invalid');
        const invalidProfile = document.querySelector('#profile-pane-edit .is-invalid');
        const initialTab = ['overview', 'edit', 'security', 'activity'].includes(statusTab)
            ? statusTab
            : ['overview', 'edit', 'security', 'activity'].includes(requestedTab)
            ? requestedTab
            : invalidPassword
                ? 'security'
                : invalidProfile
                    ? 'edit'
                    : 'overview';
        activate(initialTab);
    })();
</script>
@endpush
