@extends('layouts.app')

@section('title', 'My Email Mailbox')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Email" title="My cPanel mailbox"
        description="Connect your own cPanel email account to view its inbox in OMGComputer."
        :back-url="route('email.inbox')" back-label="Email inbox" />

    @if (session('mailbox_settings_success'))
        <div class="alert alert-success" role="status">{{ session('mailbox_settings_success') }}</div>
    @endif
    @if (session('mailbox_settings_error'))
        <div class="alert alert-danger" role="alert">{{ session('mailbox_settings_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card p-2">
        <div class="card-body">
            <p class="text-body-secondary">
                Your mailbox login is private to your account. The IMAP server address and connection settings are managed by an administrator.
            </p>
            <form method="POST" action="{{ route('email.mailbox-settings.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="mailbox-email">cPanel email address</label>
                    <input class="form-control" id="mailbox-email" name="email" type="email" maxlength="255"
                        value="{{ old('email', $mailbox?->email) }}" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="mailbox-password">Mailbox password</label>
                    <input class="form-control" id="mailbox-password" name="password" type="password"
                        autocomplete="new-password" maxlength="1000"
                        placeholder="{{ $mailbox ? 'Saved; leave blank to keep the current password' : 'Enter your cPanel mailbox password' }}"
                        {{ $mailbox ? '' : 'required' }}>
                    <div class="form-text">Stored encrypted. Your password is never shown after saving.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="mailbox-signature">Email signature</label>
                    <textarea class="form-control" id="mailbox-signature" name="signature" rows="5" maxlength="5000"
                        placeholder="Your name&#10;Your role&#10;OMG Computer">{{ old('signature', $mailbox?->signature) }}</textarea>
                    <div class="form-text">This plain-text signature is automatically added to the bottom of emails you send. Leave blank to omit it.</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">
                        <span class="fas fa-save me-2" aria-hidden="true"></span>Save mailbox
                    </button>
                    @if ($mailbox)
                        <button class="btn btn-outline-primary" type="submit" form="test-mailbox-form">
                            <span class="fas fa-plug me-2" aria-hidden="true"></span>Test connection
                        </button>
                    @endif
                </div>
            </form>
            @if ($mailbox)
                <form id="test-mailbox-form" method="POST" action="{{ route('email.mailbox-settings.test') }}">@csrf</form>
                <form method="POST" action="{{ route('email.mailbox-settings.delete') }}" class="mt-3"
                    onsubmit="return confirm('Remove your saved mailbox connection?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove mailbox connection</button>
                </form>
            @endif
        </div>
    </div>

    @role('admin')
        <p class="mt-3 text-end small text-body-secondary">
            <a href="{{ route('email.settings') }}">Manage shared email server settings</a>
        </p>
    @endrole
</div>
@endsection
