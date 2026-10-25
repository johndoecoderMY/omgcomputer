@extends('layouts.app')

@section('title', $accountType === 'customers' ? 'Create customer account' : 'Create member')

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$accountType === 'customers' ? 'Manage / Customer accounts' : 'Manage / Members'" :title="$accountType === 'customers' ? 'Create customer account' : 'Create member'"
        :description="$accountType === 'customers'
            ? 'New customer accounts can view projects and tickets linked to their account.'
            : 'New members receive a secure email verification and password setup link at their personal contact email.'"
        :back-url="route($accountType === 'customers' ? 'admin.users.customers' : 'admin.users.index')"
        :back-label="$accountType === 'customers' ? 'All customer accounts' : 'All members'" />

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card p-2" method="POST" action="{{ route($accountType === 'customers' ? 'admin.users.customers.store' : 'admin.users.store') }}">
        @csrf
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">{{ $accountType === 'customers' ? 'Email' : 'Personal contact email' }}</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required>
                @if ($accountType !== 'customers')
                    <div class="form-text">Used to sign in and receive the password setup link. Do not use the new cPanel mailbox address here.</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="{{ old('phone') }}" maxlength="50">
            </div>
            @if ($accountType !== 'customers')
            @if ($cpanelProvisioningEnabled)
                <div class="col-md-6">
                    <label class="form-label" for="mailbox_username">Preferred email name</label>
                    <div class="input-group">
                        <input class="form-control" id="mailbox_username" name="mailbox_username" value="{{ old('mailbox_username') }}"
                            maxlength="64" pattern="[A-Za-z0-9][A-Za-z0-9._+-]*[A-Za-z0-9]|[A-Za-z0-9]"
                            placeholder="firstname.lastname" aria-describedby="mailbox-username-help" required>
                        <span class="input-group-text">{{ '@'.config('services.cpanel.email_domain') }}</span>
                    </div>
                    <div class="form-text" id="mailbox-username-help">
                        Choose the preferred part before @ for the member's work email.
                        Mailbox quota: {{ (int) config('services.cpanel.email_quota') === 0 ? 'unlimited' : number_format((int) config('services.cpanel.email_quota')).' MB' }}.
                        The mailbox is provisioned by cPanel UAPI; its generated password is stored encrypted for OMGComputer inbox access.
                    </div>
                </div>
            @endif
            <div class="col-md-6">
                <label class="form-label" for="team_ids">Teams <span class="text-body-secondary">(optional)</span></label>
                <select class="form-select js-user-search" id="team_ids" name="team_ids[]" multiple data-placeholder="Select one or more teams">
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected(in_array((string) $team->id, array_map('strval', old('team_ids', [])), true))>
                            {{ $team->department?->name ? $team->department->name.' · ' : '' }}{{ $team->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Select one or more teams.</div>
            </div>
            @endif
            <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" maxlength="2000">{{ old('address') }}</textarea>
            </div>
            @if ($accountType === 'customers')
                <div class="col-md-6">
                    <label class="form-label" for="password">Temporary password</label>
                    <input class="form-control" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                    <div class="form-text">Use at least 12 characters. The customer must replace this at first sign-in.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Confirm temporary password</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                </div>
            @else
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        A secure email verification and password setup link will be emailed to the personal contact address. The member verifies the address and chooses their own password; no password is sent.
                    </div>
                </div>
            @endif
        </div>
        <div class="card-footer bg-body d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route($accountType === 'customers' ? 'admin.users.customers' : 'admin.users.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Create {{ $accountType === 'customers' ? 'customer account' : 'member' }}</button>
        </div>
    </form>
</div>
@endsection
