@extends('layouts.app')

@section('title', $user->name . ' - Profile')

@section('content')
<div class="container-fluid py-3 profile-page">
    <x-page-header :eyebrow="$isCustomerAccount ? 'Customer account' : 'Team directory'" :title="$user->name"
        :description="$isCustomerAccount ? 'Customer login account details.' : 'Member profile and team contact information.'"
        :back-url="$isCustomerAccount && $canManageAccount ? route('admin.users.customers') : route('teams.index')"
        :back-label="$isCustomerAccount && $canManageAccount ? 'Customer accounts' : 'Team directory'">
        @if ($canManageAccount)
            <x-slot:actions>
                <a class="btn btn-primary" href="{{ route('admin.users.edit', $user) }}">
                    <i class="bi bi-person-gear me-2" aria-hidden="true"></i>Manage account
                </a>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($canManageAccount && ! $isCustomerAccount)
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
    @endif

    <div class="row g-3">
        <aside class="col-12 col-xl-3">
            @include('profile._identity-card', ['isSelf' => false, 'isCustomerAccount' => $isCustomerAccount])
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
                        @unless ($isCustomerAccount)
                            <div class="col-sm-6 profile-info-item">
                                <span class="profile-info-label">Team</span>
                                <span class="profile-info-value">{{ $user->teams->pluck('name')->join(', ') ?: 'Not assigned' }}</span>
                            </div>
                            <div class="col-sm-6 profile-info-item">
                                <span class="profile-info-label">Department</span>
                                <span class="profile-info-value">{{ $user->teams->pluck('department.name')->filter()->unique()->join(', ') ?: 'Not assigned' }}</span>
                            </div>
                        @endunless
                        <div class="col-sm-6 profile-info-item">
                            <span class="profile-info-label">Member since</span>
                            <span class="profile-info-value">{{ $user->created_at->format('F Y') }}</span>
                        </div>
                        @if ($canManageAccount && ! $isCustomerAccount)
                            <div class="col-12 border-top pt-3">
                                <h3 class="h6">OMG Computer mailbox</h3>
                                @if ($user->emailMailbox)
                                    <p class="mb-0"><a href="mailto:{{ $user->emailMailbox->email }}">{{ $user->emailMailbox->email }}</a></p>
                                @elseif ($cpanelProvisioningEnabled)
                                    <form method="POST" action="{{ route('admin.users.mailbox.create', $user) }}" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="mailbox_username">Preferred email name</label>
                                            <div class="input-group">
                                                <input class="form-control" id="mailbox_username" name="mailbox_username"
                                                    value="{{ old('mailbox_username') }}" maxlength="64"
                                                    pattern="[A-Za-z0-9][A-Za-z0-9._+-]*[A-Za-z0-9]|[A-Za-z0-9]"
                                                    placeholder="firstname.lastname" required>
                                                <span class="input-group-text">{{ '@'.config('services.cpanel.email_domain') }}</span>
                                            </div>
                                            <div class="form-text">Mailbox quota: {{ (int) config('services.cpanel.email_quota') === 0 ? 'unlimited' : number_format((int) config('services.cpanel.email_quota')).' MB' }}.</div>
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-outline-primary" type="submit">Create mailbox</button>
                                        </div>
                                    </form>
                                @else
                                    <p class="small text-body-secondary mb-0">No mailbox is linked. Configure cPanel email provisioning to create one here.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@include('profile._styles')
@endsection
