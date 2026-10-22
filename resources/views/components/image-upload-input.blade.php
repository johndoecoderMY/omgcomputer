@props([
    'id',
    'name',
    'multiple' => false,
    'required' => false,
    'accept' => 'image/jpeg,image/png,image/webp',
    'cameraLabel' => 'Camera',
    'inputClass' => '',
    'cameraMediaTypeId' => null,
    'cameraMediaTypeValue' => null,
])

<div class="d-grid gap-2"
    data-image-upload
    @if ($cameraMediaTypeId) data-camera-media-type-id="{{ $cameraMediaTypeId }}" @endif
    @if ($cameraMediaTypeValue) data-camera-media-type-value="{{ $cameraMediaTypeValue }}" @endif>
    <div class="input-group">
        <input
            class="form-control {{ $inputClass }} {{ $attributes->get('class') }}"
            id="{{ $id }}"
            name="{{ $name }}"
            type="file"
            accept="{{ $accept }}"
            @if ($multiple) multiple @endif
            @if ($required) required @endif
            aria-describedby="{{ $id }}-selection"
        >
        <button class="btn btn-outline-secondary" type="button" data-image-upload-camera aria-controls="{{ $id }}">
            <span class="fas fa-camera me-1" aria-hidden="true"></span>{{ $cameraLabel }}
        </button>
    </div>
    <span class="small text-body-secondary" id="{{ $id }}-selection" data-image-upload-selection aria-live="polite"></span>
</div>

@once
    @push('scripts')
        <script>
            const initializeImageUpload = (control) => {
                if (control.dataset.imageUploadInitialized) return;
                control.dataset.imageUploadInitialized = 'true';

                const input = control.querySelector('input[type="file"]');
                const cameraButton = control.querySelector('[data-image-upload-camera]');
                const selection = control.querySelector('[data-image-upload-selection]');
                const originalAccept = input.accept;
                let cameraRequested = false;
                const mediaType = control.dataset.cameraMediaTypeId
                    ? document.getElementById(control.dataset.cameraMediaTypeId)
                    : null;

                const syncCameraButton = () => {
                    cameraButton.disabled = Boolean(mediaType)
                        && mediaType.value !== control.dataset.cameraMediaTypeValue;
                };
                syncCameraButton();
                mediaType?.addEventListener('change', syncCameraButton);

                cameraButton.addEventListener('click', () => {
                    cameraRequested = true;
                    input.accept = 'image/jpeg,image/png,image/webp';
                    input.setAttribute('capture', 'environment');
                    input.click();
                });
                input.addEventListener('click', () => {
                    if (cameraRequested) {
                        cameraRequested = false;

                        return;
                    }
                    input.removeAttribute('capture');
                    input.accept = originalAccept;
                });
                input.addEventListener('change', () => {
                    input.removeAttribute('capture');
                    input.accept = originalAccept;
                    selection.textContent = Array.from(input.files || []).map((file) => file.name).join(', ');
                });
            };

            document.querySelectorAll('[data-image-upload]').forEach(initializeImageUpload);
            new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (!(node instanceof Element)) return;
                        if (node.matches('[data-image-upload]')) initializeImageUpload(node);
                        node.querySelectorAll('[data-image-upload]').forEach(initializeImageUpload);
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        </script>
    @endpush
@endonce
