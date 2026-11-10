<section class="{{ ! empty($compact ?? false) ? 'py-3 py-lg-4' : 'py-8 py-lg-10' }} bg-body-tertiary overflow-hidden">
    <div class="container">
        <div class="row align-items-center {{ ! empty($compact ?? false) ? 'g-3' : 'g-5' }}">
            <div class="col-lg-6">
                <span class="badge rounded-pill badge-subtle-primary mb-2 px-3 py-2">{{ $eyebrow }}</span>
                <h1 class="{{ ! empty($compact ?? false) ? 'display-4' : 'display-3' }} fw-bold text-1000 mb-2">{{ $heading }}</h1>
                <p class="lead text-700 mb-0">{{ $description }}</p>
            </div>
            <div class="col-lg-6 {{ ! empty($compact ?? false) ? 'd-none d-md-block' : '' }}">
                <img
                    class="img-fluid w-100 rounded-4 shadow-lg {{ ! empty($compact ?? false) ? 'object-fit-cover' : '' }}"
                    src="{{ asset('assets/img/omg/computer-repair-placeholder.svg') }}"
                    alt="Placeholder illustration of computer equipment and repair tools"
                    width="960"
                    height="680"
                    @if (! empty($compact ?? false)) style="max-height: 220px;" @endif
                    fetchpriority="high"
                >
            </div>
        </div>
    </div>
</section>
