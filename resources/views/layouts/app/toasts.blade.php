@php
    $toasts = collect([
        'success' => ['title' => 'Success', 'color' => 'success'],
        'error' => ['title' => 'Error', 'color' => 'danger'],
        'warning' => ['title' => 'Warning', 'color' => 'warning'],
        'info' => ['title' => 'Information', 'color' => 'info'],
    ])->filter(fn ($toast, $key) => session()->has($key))
        ->map(fn ($toast, $key) => [...$toast, 'message' => session($key), 'key' => $key])
        ->values();
@endphp

@if ($toasts->isNotEmpty())
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090"
        aria-live="polite" aria-atomic="false">
        @foreach ($toasts as $toast)
            <div class="toast border-0 shadow" role="{{ $toast['key'] === 'error' ? 'alert' : 'status' }}"
                aria-live="{{ $toast['key'] === 'error' ? 'assertive' : 'polite' }}"
                data-flash-toast data-bs-delay="{{ $toast['key'] === 'error' ? 8000 : 5000 }}">
                <div class="toast-header">
                    <span class="rounded-circle bg-{{ $toast['color'] }} me-2" style="width: .65rem; height: .65rem"></span>
                    <strong class="me-auto">{{ $toast['title'] }}</strong>
                    <button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">{{ $toast['message'] }}</div>
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-flash-toast]').forEach((toast) => {
                bootstrap.Toast.getOrCreateInstance(toast).show();
            });
        </script>
    @endpush
@endif
