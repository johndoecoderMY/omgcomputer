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
                    <form method="POST" action="{{ route('reports.integrations.notifications.send') }}" data-notification-test-form>
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <span class="bi bi-bell me-2" aria-hidden="true"></span><span data-notification-test-label>Send test notification</span>
                        </button>
                    </form>
                    <div class="alert d-none mt-3" role="status" aria-live="polite" data-notification-test-result></div>
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
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('reports.integrations.email.send') }}">
                            @csrf
                            <button class="btn btn-outline-primary" type="submit">
                                <span class="bi bi-send me-2" aria-hidden="true"></span>Send test email
                            </button>
                        </form>
                        <a class="btn btn-outline-secondary" href="{{ route('reports.integrations.email.preview') }}">
                            <span class="bi bi-window me-2" aria-hidden="true"></span>Preview email layout
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    The email is sent to your own account address. Delivery errors are reported by the configured mail transport.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-notification-test-form]');
            const status = document.querySelector('[data-notification-test-result]');
            if (!(form instanceof HTMLFormElement) || !(status instanceof HTMLElement)) {
                return;
            }

            const button = form.querySelector('[type="submit"]');
            const label = form.querySelector('[data-notification-test-label]');
            const defaultLabel = label?.textContent || '';
            const showStatus = (message, type) => {
                status.textContent = message;
                status.className = `alert alert-${type} mt-3`;
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (button?.disabled) {
                    return;
                }

                if (button) {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                }
                if (label) {
                    label.textContent = 'Sending...';
                }
                showStatus('Sending test notification...', 'info');

                try {
                    const response = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => null);

                    if (!response.ok || payload?.success !== true) {
                        const errors = Object.values(payload?.errors || {}).flat();
                        throw new Error(errors.join('\n') || payload?.message || 'The test notification could not be sent.');
                    }

                    if (typeof payload.notification_id === 'string') {
                        void window.playAppNotificationSoundOnce?.(payload.notification_id);
                    }
                    showStatus(payload.message || 'A test notification was sent to your account.', 'success');
                } catch (error) {
                    showStatus(error instanceof Error ? error.message : 'The test notification could not be sent.', 'danger');
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                    if (label) {
                        label.textContent = defaultLabel;
                    }
                }
            });
        })();
    </script>
@endpush
