@extends('layouts.app')

@section('title', 'Notification & Email Tests')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="Notification & Email Tests"
        description="Check workspace notifications and outgoing email using your own admin account."
        :back-url="route('reports.index')" back-label="Reports overview" />

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex align-items-center gap-3">
                    <span class="bi bi-bell fs-2 text-primary" aria-hidden="true"></span>
                    <div>
                        <h2 class="h5 mb-0 text-primary">Test workspace notification</h2>
                        <span class="small text-body-secondary">Database and real-time notification</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($result = session('notification_test_result'))
                        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
                            {{ $result['message'] }}
                        </div>
                    @endif
                    <p>Send a test notification to your own account. It will appear in your notification list and can be read in the notification menu.</p>
                    <form method="POST" action="{{ route('reports.integrations.notifications.send') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <span class="bi bi-bell me-2" aria-hidden="true"></span>Send test notification
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex align-items-center gap-3">
                    <span class="bi bi-envelope fs-2 text-primary" aria-hidden="true"></span>
                    <div>
                        <h2 class="h5 mb-0 text-primary">Test outgoing email</h2>
                        <span class="small text-body-secondary">Mailer: {{ $mailer }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($result = session('email_test_result'))
                        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
                            {{ $result['message'] }}
                        </div>
                    @endif
                    <p class="mb-3">Send a test message to <strong>{{ auth()->user()->email }}</strong>.</p>
                    <p class="small text-body-secondary">From address: {{ $fromAddress ?: 'Not configured' }}</p>
                    <p><a href="{{ route('email.settings') }}">Configure an SMTP server</a> to send mail through your existing account.</p>
                    <form method="POST" action="{{ route('reports.integrations.email.send') }}">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">
                            <span class="bi bi-send me-2" aria-hidden="true"></span>Send test email
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    The email is sent to your own account address. Delivery errors are reported by the configured mail transport.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
