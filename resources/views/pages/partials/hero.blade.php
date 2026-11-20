@php
    $imagePath = $image_path ?? 'assets/img/omg/computer-repair-placeholder.svg';
    $imageAlt = $image_alt ?? 'Illustration of computer equipment and repair tools.';
    $imageFit = $image_fit ?? 'cover';
@endphp
<section class="{{ ! empty($compact ?? false) ? 'py-3 py-lg-4' : 'py-8 py-lg-10' }} bg-body-tertiary overflow-hidden">
    <div class="container">
        <div class="row align-items-center {{ ! empty($compact ?? false) ? 'g-3' : 'g-5' }}">
            <div class="col-lg-6">
                <span class="badge rounded-pill badge-subtle-primary mb-2 px-3 py-2">{{ __($eyebrow) }}</span>
                <h1 class="{{ ! empty($compact ?? false) ? 'display-4' : 'display-3' }} fw-bold text-1000 mb-2">{{ __($heading) }}</h1>
                <p class="lead text-700 mb-0">{{ __($description) }}</p>
            </div>
            <div class="col-lg-6 {{ ! empty($compact ?? false) ? 'd-none d-md-block' : '' }}">
                <img
                    class="img-fluid w-100 rounded-4 shadow-lg"
                    src="{{ asset($imagePath) }}"
                    alt="{{ __($imageAlt) }}"
                    width="{{ $image_width ?? 960 }}"
                    height="{{ $image_height ?? 680 }}"
                    @if (! empty($compact ?? false)) style="height: 220px; object-fit: {{ $imageFit }};" @endif
                    fetchpriority="high"
                >
            </div>
        </div>
    </div>
</section>
