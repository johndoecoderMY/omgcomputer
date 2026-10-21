@extends('layouts.app')

@section('title', 'Email Server Settings')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Email" title="Email server settings"
        description="Configure the shared outgoing SMTP service and incoming IMAP server. Each user connects their own mailbox."
        :back-url="route('email.inbox')" back-label="Email inbox" />

    @if ($result = session('email_settings_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">{{ $result['message'] }}</div>
    @endif
    @if ($result = session('imap_test_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">{{ $result['message'] }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('email.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <section class="card h-100 p-2" aria-labelledby="smtp-heading">
                    <div class="card-header bg-body">
                        <h2 class="h5 mb-1" id="smtp-heading">Outgoing mail (SMTP)</h2>
                        <p class="small text-body-secondary mb-0">Used by the application to send email through your server.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="smtp-host">SMTP host</label>
                                <input class="form-control" id="smtp-host" name="smtp_host" value="{{ old('smtp_host', $settings->smtp_host) }}" placeholder="mail.example.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="smtp-port">Port</label>
                                <input class="form-control" id="smtp-port" name="smtp_port" type="number" min="1" max="65535" value="{{ old('smtp_port', $settings->smtp_port) }}" placeholder="587">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="smtp-encryption">Encryption</label>
                                <select class="form-select" id="smtp-encryption" name="smtp_encryption">
                                    @foreach (['tls' => 'STARTTLS', 'ssl' => 'SSL/TLS', 'none' => 'None'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('smtp_encryption', $settings->smtp_encryption) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="smtp-username">Username</label>
                                <input class="form-control" id="smtp-username" name="smtp_username" value="{{ old('smtp_username', $settings->smtp_username) }}" autocomplete="username">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="smtp-password">Password</label>
                                <input class="form-control" id="smtp-password" name="smtp_password" type="password" autocomplete="new-password" placeholder="{{ $smtpPasswordConfigured ? 'Saved; leave blank to keep current password' : 'Leave blank if the server does not require authentication' }}">
                                <div class="form-text">Stored encrypted. The saved password is never shown here.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="from-address">Sender email address</label>
                                <input class="form-control" id="from-address" name="from_address" type="email" value="{{ old('from_address', $settings->from_address) }}" placeholder="support@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="from-name">Sender name</label>
                                <input class="form-control" id="from-name" name="from_name" value="{{ old('from_name', $settings->from_name) }}" placeholder="{{ config('app.name') }}">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-6">
                <section class="card h-100 p-2" aria-labelledby="imap-heading">
                    <div class="card-header bg-body">
                        <h2 class="h5 mb-1" id="imap-heading">Incoming mail (IMAP)</h2>
                        <p class="small text-body-secondary mb-0">Connect a mailbox to read and manage received email here. The PHP IMAP extension must be enabled on the web server.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="imap-host">IMAP host</label>
                                <input class="form-control" id="imap-host" name="imap_host" value="{{ old('imap_host', $settings->imap_host) }}" placeholder="imap.example.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="imap-port">Port</label>
                                <input class="form-control" id="imap-port" name="imap_port" type="number" min="1" max="65535" value="{{ old('imap_port', $settings->imap_port) }}" placeholder="993">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="imap-encryption">Encryption</label>
                                <select class="form-select" id="imap-encryption" name="imap_encryption">
                                    @foreach (['ssl' => 'SSL/TLS', 'tls' => 'STARTTLS', 'none' => 'None'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('imap_encryption', $settings->imap_encryption) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="imap-folder">Inbox folder</label>
                                <input class="form-control" id="imap-folder" name="imap_folder" value="{{ old('imap_folder', $settings->imap_folder) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="imap-archive-folder">Archive folder</label>
                                <input class="form-control" id="imap-archive-folder" name="imap_archive_folder" value="{{ old('imap_archive_folder', $settings->imap_archive_folder) }}" required>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 my-3">
            <button class="btn btn-outline-primary" type="submit" form="test-imap-form">
                <span class="fas fa-plug me-2" aria-hidden="true"></span>Test IMAP connection
            </button>
            <button class="btn btn-primary" type="submit">
                <span class="fas fa-save me-2" aria-hidden="true"></span>Save email settings
            </button>
        </div>
    </form>
    <form id="test-imap-form" method="POST" action="{{ route('email.settings.test-imap') }}">@csrf</form>
    <p class="text-end small text-body-secondary">
        The IMAP test uses your own mailbox credentials.
        <a href="{{ route('email.mailbox-settings') }}">Configure your mailbox</a> before testing.
    </p>

    <div class="card p-2">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1">Test outgoing email</h2>
                <p class="small text-body-secondary mb-0">Send a test message to {{ auth()->user()->email }} using the saved SMTP settings.</p>
            </div>
            <form method="POST" action="{{ route('email.settings.test-smtp') }}">
                @csrf
                <button class="btn btn-outline-primary" type="submit">
                    <span class="fas fa-paper-plane me-2" aria-hidden="true"></span>Send test email
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
