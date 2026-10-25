@extends('layouts.app')

@section('title', 'cPanel Connection Test')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="cPanel Email UAPI"
        description="Test the cPanel hostname, API token, and email-domain access with a read-only mailbox list request."
        :back-url="route('reports.integrations.notifications')" back-label="Integrations" />

    @if ($result = session('cpanel_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
            <strong>{{ $result['success'] ? 'Connection successful' : 'Connection not verified' }}</strong>
            <div>{{ $result['message'] }}</div>
            @if ($result['success'] && isset($result['mailbox_count']))
                <div class="small mt-1">Mailboxes found: {{ $result['mailbox_count'] }}</div>
            @endif
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <section class="card h-100 p-2">
                <div class="card-header bg-body d-flex align-items-center gap-3">
                    <span class="bi bi-envelope-check fs-2 text-primary" aria-hidden="true"></span>
                    <div>
                        <h2 class="h5 mb-0">Connection setup</h2>
                        <span class="small text-body-secondary">Secure cPanel UAPI · port {{ $port }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span>Hostname</span>
                            <code>{{ $host ?: 'Not configured' }}</code>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span>cPanel username</span>
                            <span>{{ $usernameConfigured ? 'Configured' : 'Missing' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span>API token</span>
                            <span>{{ filled(config('services.cpanel.api_token')) ? 'Configured (hidden)' : 'Missing' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span>Email domain</span>
                            <code>{{ $domain ?: 'Not configured' }}</code>
                        </li>
                    </ul>
                    <form method="POST" action="{{ route('reports.integrations.cpanel.check') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit" @disabled(! $cpanelReady)>
                            <span class="bi bi-plug me-2" aria-hidden="true"></span>Test cPanel connection
                        </button>
                    </form>
                    @unless ($cpanelReady)
                        <p class="small text-body-secondary mt-3 mb-0">
                            Set the missing <code>CPANEL_*</code> values in the server environment and clear Laravel's cached configuration before testing.
                        </p>
                    @endunless
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    This test only lists mailboxes for the configured domain. It does not create or change any mailbox and never displays the API token.
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-7">
            <section class="card h-100 p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0">What this test checks</h2>
                </div>
                <div class="card-body">
                    <p>The test sends a read-only <code>Email/list_pops</code> request to:</p>
                    <p><code>https://{{ $host ?: 'your-cpanel-host' }}:{{ $port }}/execute/Email/list_pops</code></p>
                    <ul class="mb-0">
                        <li>Whether the configured hostname and port can reach cPanel over HTTPS.</li>
                        <li>Whether the cPanel username and API token are accepted.</li>
                        <li>Whether cPanel can list mailboxes for <code>{{ $domain ?: 'your configured email domain' }}</code>.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
