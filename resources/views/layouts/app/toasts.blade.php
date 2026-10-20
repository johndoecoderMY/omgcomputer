@php
    $toasts = collect([
        'success' => ['title' => 'Success', 'color' => 'success'],
        'error' => ['title' => 'Error', 'color' => 'danger'],
        'warning' => ['title' => 'Warning', 'color' => 'warning'],
        'info' => ['title' => 'Information', 'color' => 'info'],
    ])->filter(fn ($toast, $key) => session()->has($key))
        ->map(fn ($toast, $key) => [...$toast, 'message' => session($key), 'key' => $key])
        ->values();

    if ($errors->any()) {
        $toasts->push([
            'title' => 'Please check your input',
            'color' => 'danger',
            'message' => $errors->all(),
            'key' => 'validation',
        ]);
    }
@endphp

<style>
    .app-toast-container {
        bottom: calc(env(safe-area-inset-bottom, 0px) + .75rem);
        top: auto !important;
        max-width: min(26rem, calc(100vw - 1.5rem));
    }

    @media (max-width: 575.98px) {
        .app-toast-container {
            right: auto !important;
            left: 50%;
            width: calc(100vw - 1rem);
            max-width: 26rem;
            padding: 0 !important;
            transform: translateX(-50%);
        }

        .app-toast-container .toast {
            width: 100%;
        }
    }
</style>

@if ($toasts->isNotEmpty())
    <div class="toast-container app-toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090"
        aria-live="polite" aria-atomic="false" data-app-toast-container>
        @foreach ($toasts as $toast)
            <div class="toast border-0 shadow" role="{{ in_array($toast['key'], ['error', 'validation'], true) ? 'alert' : 'status' }}"
                aria-live="{{ in_array($toast['key'], ['error', 'validation'], true) ? 'assertive' : 'polite' }}"
                data-flash-toast data-bs-delay="{{ in_array($toast['key'], ['error', 'validation'], true) ? 8000 : 5000 }}">
                <div class="toast-header">
                    <span class="rounded-circle bg-{{ $toast['color'] }} me-2" style="width: .65rem; height: .65rem"></span>
                    <strong class="me-auto">{{ $toast['title'] }}</strong>
                    <button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" style="white-space: pre-line">
                    {{ is_array($toast['message']) ? implode("\n", $toast['message']) : $toast['message'] }}
                </div>
            </div>
        @endforeach
    </div>
@endif

@push('scripts')
    <script>
        (() => {
            const toastContainer = () => {
                let container = document.querySelector('[data-app-toast-container]');
                if (!container) {
                    container = document.createElement('div');
                    container.className = 'toast-container app-toast-container position-fixed bottom-0 end-0 p-3';
                    container.dataset.appToastContainer = '';
                    container.style.zIndex = '1090';
                    container.setAttribute('aria-live', 'polite');
                    container.setAttribute('aria-atomic', 'false');
                    document.body.append(container);
                }

                return container;
            };

            const showToast = (title, message, color, isError) => {
                const toast = document.createElement('div');
                toast.className = 'toast border-0 shadow';
                toast.setAttribute('role', isError ? 'alert' : 'status');
                toast.setAttribute('aria-live', isError ? 'assertive' : 'polite');
                toast.dataset.bsDelay = isError ? '8000' : '5000';
                toast.dataset.flashToast = '';

                const header = document.createElement('div');
                header.className = 'toast-header';
                const indicator = document.createElement('span');
                indicator.className = `rounded-circle bg-${color} me-2`;
                indicator.style.width = '.65rem';
                indicator.style.height = '.65rem';
                const heading = document.createElement('strong');
                heading.className = 'me-auto';
                heading.textContent = title;
                const close = document.createElement('button');
                close.className = 'btn-close';
                close.type = 'button';
                close.dataset.bsDismiss = 'toast';
                close.setAttribute('aria-label', 'Close');
                header.append(indicator, heading, close);

                const body = document.createElement('div');
                body.className = 'toast-body';
                body.style.whiteSpace = 'pre-line';
                body.textContent = message;
                toast.append(header, body);
                toastContainer().append(toast);
                bootstrap.Toast.getOrCreateInstance(toast).show();
            };

            const normalize = (value) => value.replace(/\s+/g, ' ').trim().toLocaleLowerCase();
            const existingMessages = () => Array.from(document.querySelectorAll('[data-flash-toast] .toast-body'))
                .map((body) => normalize(body.textContent || ''))
                .filter(Boolean);

            document.querySelectorAll('[data-flash-toast]').forEach((toast) => {
                bootstrap.Toast.getOrCreateInstance(toast).show();
            });

            document.querySelectorAll('.alert-success, .alert-danger').forEach((alert) => {
                if (alert.parentElement?.closest('.alert-success, .alert-danger')) {
                    return;
                }

                const message = (alert.innerText || alert.textContent || '').trim();
                const normalizedMessage = normalize(message);
                const isError = alert.classList.contains('alert-danger');
                const isDuplicate = existingMessages().some((existing) => {
                    if (normalizedMessage.includes(existing)) {
                        return true;
                    }

                    const pieces = isError
                        ? existing.split(/\s*[•\n]\s*/).map(normalize).filter(Boolean)
                        : [existing];

                    return pieces.length > 0 && pieces.every((piece) => normalizedMessage.includes(piece));
                });

                if (!isDuplicate && message) {
                    showToast(isError ? 'Error' : 'Success', message, isError ? 'danger' : 'success', isError);
                }

                alert.remove();
            });
        })();
    </script>
@endpush
