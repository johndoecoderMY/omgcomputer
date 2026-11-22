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
                @if ($cpanelProvisioningEnabled)
                    <div class="col-12">
                        <label class="form-label" for="mailbox_username">Preferred email name</label>
                        <div class="input-group">
                            <input class="form-control" id="mailbox_username" name="mailbox_username" value="{{ old('mailbox_username') }}"
                                maxlength="64" pattern="[A-Za-z0-9][A-Za-z0-9._+-]*[A-Za-z0-9]|[A-Za-z0-9]"
                                placeholder="firstname.lastname" aria-describedby="mailbox-username-help"
                                @disabled(old('skip_mailbox')) @required(! old('skip_mailbox'))>
                            <span class="input-group-text">{{ '@'.config('services.cpanel.email_domain') }}</span>
                        </div>
                        <div class="form-text">Choose the part before @ for the member's work email.</div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-use-member-name>
                                <span class="bi bi-person-vcard me-1" aria-hidden="true"></span>Use member name
                            </button>
                            <input class="btn-check" id="skip_mailbox" name="skip_mailbox" type="checkbox" value="1"
                                @checked(old('skip_mailbox')) aria-controls="mailbox_username">
                            <label class="btn btn-sm btn-outline-secondary" for="skip_mailbox">
                                <span class="bi bi-envelope-slash me-1" aria-hidden="true"></span>Set up mailbox later
                            </label>
                        </div>
                        <div class="form-text mt-2" id="mailbox-setup-status" aria-live="polite">
                            The mailbox will be provisioned and connected automatically.
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 small text-body-secondary" id="mailbox-username-help" data-mailbox-provisioning-details>
                            <span class="badge rounded-pill bg-body-secondary text-body border">
                                Mailbox quota: {{ (int) config('services.cpanel.email_quota') === 0 ? 'Unlimited' : number_format((int) config('services.cpanel.email_quota')).' MB' }}
                            </span>
                            <span>Separate generated password, stored encrypted</span>
                        </div>
                    </div>
                @endif
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

@push('scripts')
    <script>
        (() => {
            const nameInput = document.getElementById('name');
            const mailboxInput = document.getElementById('mailbox_username');
            const useNameButton = document.querySelector('[data-use-member-name]');
            const skipMailbox = document.getElementById('skip_mailbox');
            const setupStatus = document.getElementById('mailbox-setup-status');
            const provisioningDetails = document.querySelector('[data-mailbox-provisioning-details]');

            if (!nameInput || !mailboxInput || !useNameButton || !skipMailbox || !setupStatus || !provisioningDetails) {
                return;
            }

            useNameButton.addEventListener('click', () => {
                mailboxInput.value = nameInput.value
                    .normalize('NFKD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '.')
                    .replace(/^\.+|\.+$/g, '')
                    .slice(0, 64)
                    .replace(/\.+$/g, '');
            });

            const updateMailboxSetup = () => {
                mailboxInput.disabled = skipMailbox.checked;
                mailboxInput.required = !skipMailbox.checked;
                useNameButton.disabled = skipMailbox.checked;
                provisioningDetails.hidden = skipMailbox.checked;
                setupStatus.textContent = skipMailbox.checked
                    ? 'No mailbox will be created. The member can connect one later from Email settings.'
                    : 'The mailbox will be provisioned and connected automatically.';
            };

            skipMailbox.addEventListener('change', updateMailboxSetup);
            updateMailboxSetup();
        })();
    </script>
@endpush
