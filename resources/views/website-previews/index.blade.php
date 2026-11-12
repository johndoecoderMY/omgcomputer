@extends('layouts.guest')

@section('title', 'Website Demos & Previews')
@section('meta_description', 'Explore responsive website demos for cafes, shops, events, and more. Each preview is a sample website, not a completed client project.')

@section('content')
@php
    $portfolioWhatsAppUrl = 'https://wa.me/60104224350?text='.rawurlencode('Hi OMG Computer, I would like to ask about your services.');
    $portfolioContactEmail = 'support@omgcomputer.my';
@endphp
<style>
    .portfolio-page { --portfolio-ink: #0b1727; --portfolio-muted: #607087; }
    .portfolio-hero {
        position: relative;
        overflow: hidden;
        padding: 8rem 0 4.5rem;
        background:
            radial-gradient(ellipse at 82% 48%, rgba(var(--falcon-primary-rgb), .18), transparent 36rem),
            linear-gradient(115deg, #0b1727 0%, #14253a 100%);
    }
    .portfolio-hero::after {
        position: absolute;
        right: -10rem;
        bottom: -19rem;
        width: 36rem;
        height: 36rem;
        border: 1px solid rgba(255, 255, 255, .09);
        border-radius: 50%;
        content: "";
        pointer-events: none;
    }
    .portfolio-hero-content { position: relative; z-index: 1; }
    .portfolio-eyebrow {
        color: #ff7849;
        background: rgba(var(--falcon-primary-rgb), .12);
        border: 1px solid rgba(var(--falcon-primary-rgb), .28);
        letter-spacing: .14em;
    }
    .portfolio-title {
        max-width: 650px;
        color: #fff;
        font-size: clamp(2.8rem, 5.6vw, 5rem);
        line-height: .99;
        letter-spacing: -.055em;
    }
    .portfolio-title em { color: #ff7849; font-family: Georgia, serif; font-weight: 400; }
    .portfolio-intro { max-width: 520px; color: #c6d2e0; }
    .portfolio-mosaic { position: relative; min-height: 490px; perspective: 1200px; }
    .portfolio-mosaic-card {
        position: absolute;
        display: block;
        padding: 0;
        overflow: hidden;
        cursor: zoom-in;
        border: 7px solid #fff;
        border-radius: 12px;
        background: #e8edf4;
        box-shadow: 0 22px 60px rgba(19, 34, 54, .2);
        transition: transform .25s ease, box-shadow .25s ease;
    }
    .portfolio-mosaic-card:hover { z-index: 5; box-shadow: 0 28px 70px rgba(19, 34, 54, .28); }
    .portfolio-mosaic-card img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .portfolio-mosaic-card span {
        position: absolute;
        right: .6rem;
        bottom: .6rem;
        left: .6rem;
        overflow: hidden;
        padding: .4rem .65rem;
        border-radius: 6px;
        background: rgba(15, 23, 37, .82);
        color: #fff;
        font-size: .75rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .portfolio-mosaic-card-1 { top: 3%; right: 2%; width: 58%; height: 42%; transform: rotate(7deg); }
    .portfolio-mosaic-card-1:hover { transform: rotate(4deg) translateY(-5px); }
    .portfolio-mosaic-card-2 { top: 25%; left: 1%; z-index: 2; width: 61%; height: 43%; transform: rotate(-8deg); }
    .portfolio-mosaic-card-2:hover { transform: rotate(-4deg) translateY(-5px); }
    .portfolio-mosaic-card-3 { right: 0; bottom: 1%; width: 58%; height: 42%; transform: rotate(5deg); }
    .portfolio-mosaic-card-3:hover { transform: rotate(2deg) translateY(-5px); }
    .portfolio-mosaic-empty {
        position: absolute;
        inset: 12% 4%;
        display: grid;
        place-items: center;
        border: 1px dashed rgba(var(--falcon-primary-rgb), .35);
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, .6);
        color: var(--portfolio-muted);
        text-align: center;
    }
    .portfolio-showcase { padding: 5rem 0; background: var(--falcon-gray-100, #f8f9fa); }
    .portfolio-contact { background: #0b1727; }
    .portfolio-contact .contact-kicker,
    .portfolio-contact .contact-icon { color: #ff4b0b; }
    .portfolio-contact .contact-copy,
    .portfolio-contact-card p { color: #a9bdd5; }
    .portfolio-contact-card { background: #111f30; }
    .portfolio-contact-card h3 { color: #fff; }
    .portfolio-contact-card .btn-outline-primary { color: #ff4b0b; border-color: #ff4b0b; }
    .portfolio-contact-card .btn-outline-primary:hover { color: #fff; background: #ff4b0b; }
    .portfolio-card { transition: transform .2s ease, box-shadow .2s ease; }
    .portfolio-card:hover { transform: translateY(-4px); box-shadow: 0 1rem 2rem rgba(19, 34, 54, .12) !important; }
    .portfolio-lightbox { background: rgba(8, 13, 22, .96); }
    .portfolio-lightbox .modal-dialog { max-width: min(96vw, 1500px); }
    .portfolio-lightbox .modal-content { border: 0; background: transparent; color: #fff; }
    .portfolio-lightbox-image-wrap { display: grid; min-height: min(76vh, 900px); place-items: center; }
    .portfolio-lightbox-image { display: block; max-width: 100%; max-height: 76vh; object-fit: contain; }
    .portfolio-lightbox-control {
        position: absolute;
        top: 50%;
        z-index: 2;
        width: 3rem;
        height: 3rem;
        transform: translateY(-50%);
        border: 1px solid rgba(255, 255, 255, .4);
        border-radius: 50%;
        background: rgba(15, 23, 37, .72);
        color: #fff;
    }
    .portfolio-lightbox-control:hover { background: #fff; color: #0b1727; }
    .portfolio-lightbox-previous { left: .75rem; }
    .portfolio-lightbox-next { right: .75rem; }
    .portfolio-lightbox-close { filter: invert(1); }
    @media (max-width: 991.98px) {
        .portfolio-hero { padding-top: 7.5rem; }
        .portfolio-mosaic { min-height: 430px; margin-top: 1rem; }
    }
    @media (max-width: 575.98px) {
        .portfolio-hero { padding: 7rem 0 3rem; }
        .portfolio-title { font-size: clamp(2.7rem, 13vw, 4rem); }
        .portfolio-mosaic { min-height: 320px; margin: 0 -.5rem; }
        .portfolio-mosaic-card { border-width: 5px; border-radius: 9px; }
        .portfolio-mosaic-card-1 { width: 58%; height: 40%; }
        .portfolio-mosaic-card-2 { width: 62%; height: 42%; }
        .portfolio-mosaic-card-3 { width: 58%; height: 40%; }
        .portfolio-showcase { padding: 3.5rem 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .portfolio-mosaic-card, .portfolio-card { transition: none; }
    }
</style>

<div class="portfolio-page">
    <section class="portfolio-hero">
        <div class="container portfolio-hero-content">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-5">
                    <span class="portfolio-eyebrow badge rounded-pill px-3 py-2 text-uppercase fw-bold">
                        OMG Computer · Website design
                    </span>
                    <h1 class="portfolio-title fw-bold mt-4 mb-4">
                        See what your website could <em>be.</em>
                    </h1>
                    <p class="portfolio-intro lead mb-4">
                        Explore responsive website demos for cafes, shops, events, and more. Each preview is a starting point for a website shaped around your business.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="#portfolio">
                            Explore demos <span class="bi bi-arrow-down ms-1" aria-hidden="true"></span>
                        </a>
                        <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="#contact">
                            Request a website
                        </a>
                    </div>
                    <p class="small  mt-3 mb-0">
                        Demo websites are samples, not completed client projects.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="portfolio-mosaic" role="group" aria-label="Screenshots of OMG Computer website demos">
                        @forelse ($previews->take(3) as $preview)
                            @php($galleryUrls = [Storage::disk('public')->url($preview->image_path), ...collect($preview->gallery_images ?? [])->map(fn (string $path): string => Storage::disk('public')->url($path))->all()])
                            <button class="portfolio-mosaic-card portfolio-mosaic-card-{{ $loop->iteration }}"
                                type="button" data-portfolio-lightbox
                                data-gallery="{{ json_encode($galleryUrls, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                data-gallery-title="{{ $preview->title }}"
                                data-preview-url="{{ $preview->preview_url }}"
                                data-preview-label="{{ $preview->category === 'Concept demo' ? 'View concept' : ($preview->category === 'Website demo' ? 'View demo' : 'View website') }}"
                                aria-label="View images for {{ $preview->title }}">
                                <img src="{{ Storage::disk('public')->url($preview->image_path) }}"
                                    alt="" fetchpriority="{{ $loop->first ? 'high' : 'auto' }}">
                                <span>{{ $preview->title }}</span>
                            </button>
                        @empty
                            <div class="portfolio-mosaic-empty">
                                <div>
                                    <span class="bi bi-window-stack display-4 d-block mb-3" aria-hidden="true"></span>
                                    <span>Website demos will appear here.</span>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="portfolio-showcase" id="portfolio">
        <div class="container">
            <div class="row align-items-end justify-content-between g-3 mb-4">
                <div class="col-lg-8">
                    <span class="text-primary text-uppercase fw-bold small">Ideas to get you started</span>
                    <h2 class="display-6 fw-bold text-1000 mt-2 mb-2">Explore website previews</h2>
                    <p class="text-700 mb-0">Browse interactive demos and their screenshots. Each sample can inspire a website shaped around your business.</p>
                </div>
                <div class="col-lg-auto">
                    <span class="badge rounded-pill bg-body-tertiary border text-body-secondary px-3 py-2">
                        {{ $previews->count() }} {{ \Illuminate\Support\Str::plural('website preview', $previews->count()) }}
                    </span>
                </div>
            </div>

            @if ($previews->isNotEmpty())
                <div class="row g-4">
                    @foreach ($previews as $preview)
                        <div class="col-md-6 col-xl-4">
                            @php($galleryUrls = [Storage::disk('public')->url($preview->image_path), ...collect($preview->gallery_images ?? [])->map(fn (string $path): string => Storage::disk('public')->url($path))->all()])
                            <article class="portfolio-card card h-100 border-0 shadow-sm overflow-hidden">
                                <button class="btn d-block w-100 bg-body-tertiary p-0 border-0" type="button"
                                    data-portfolio-lightbox
                                    data-gallery="{{ json_encode($galleryUrls, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                    data-gallery-title="{{ $preview->title }}"
                                    data-preview-url="{{ $preview->preview_url }}"
                                    data-preview-label="{{ $preview->category === 'Concept demo' ? 'View concept' : ($preview->category === 'Website demo' ? 'View demo' : 'View website') }}"
                                    aria-label="View images for {{ $preview->title }}">
                                    <img class="w-100" src="{{ Storage::disk('public')->url($preview->image_path) }}"
                                        alt="Website demo screenshot: {{ $preview->title }}"
                                        loading="lazy" decoding="async" style="aspect-ratio: 16 / 10; object-fit: cover;">
                                </button>
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        @if ($preview->category)
                                            <span class="badge text-bg-light border">{{ $preview->category }}</span>
                                        @endif
                                        <span class="small text-body-secondary">{{ $preview->category === 'Website demo' ? 'Interactive demo' : 'Design concept' }}</span>
                                    </div>
                                    <h2 class="h4 text-1000">{{ $preview->title }}</h2>
                                    @if ($preview->description)
                                        <p class="text-700 mb-0">{{ $preview->description }}</p>
                                    @endif
                                </div>
                                <div class="card-footer bg-body border-0 px-4 pb-4 d-flex flex-wrap gap-2">
                                    <button class="btn btn-primary rounded-pill px-4" type="button"
                                        data-portfolio-lightbox
                                        data-gallery="{{ json_encode($galleryUrls, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                        data-gallery-title="{{ $preview->title }}"
                                        data-preview-url="{{ $preview->preview_url }}"
                                        data-preview-label="{{ $preview->category === 'Concept demo' ? 'View concept' : ($preview->category === 'Website demo' ? 'View demo' : 'View website') }}">
                                        View images <span class="bi bi-arrows-fullscreen ms-1" aria-hidden="true"></span>
                                    </button>
                                    @if ($preview->preview_url)
                                        <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ $preview->preview_url }}" target="_blank" rel="noopener noreferrer">
                                            {{ $preview->category === 'Concept demo' ? 'View concept' : ($preview->category === 'Website demo' ? 'View demo' : 'Live website') }}
                                            <span class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></span>
                                        </a>
                                    @endif
                                    <a class="btn btn-outline-primary rounded-pill px-4" href="#contact">Make one for me</a>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="card border-0 shadow-sm text-center p-4 p-md-5">
                    <div class="card-body">
                        <span class="bi bi-window-stack display-4 text-primary" aria-hidden="true"></span>
                        <h3 class="h3 text-1000 mt-3">Portfolio coming soon</h3>
                        <p class="text-700">We’re preparing website demos to showcase here. Tell us about the website you have in mind.</p>
                        <a class="btn btn-primary rounded-pill px-4" href="{{ url('/') }}#website-pricing">Explore website packages</a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="portfolio-contact py-7" id="contact" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="contact-kicker fw-bold text-uppercase fs-10">Ready for the next step?</span>
                    <h2 class="h2 fw-bold text-white mt-2 mb-3">Let’s get your computer sorted.</h2>
                    <p class="contact-copy lead mb-4">Tell us what is happening and what device needs attention. We’ll help you figure out the next step.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary rounded-pill px-4">Book a service</a>
                        <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-pill px-4">Go home</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="portfolio-contact-card card border-0 shadow-lg p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="contact-icon bi bi-headset fs-2 mb-3" aria-hidden="true"></span>
                            <h3 class="h3 fw-bold">Need a hand?</h3>
                            <p class="mb-0">Get in touch with OMG Computer to discuss a repair, ask about support, or find out what to do next.</p>
                            <a class="btn btn-outline-primary rounded-pill px-4 mt-3" href="mailto:{{ $portfolioContactEmail }}">
                                <span class="bi bi-envelope me-2" aria-hidden="true"></span>{{ $portfolioContactEmail }}
                            </a>
                            <a class="btn btn-success rounded-pill px-4 mt-3" href="{{ $portfolioWhatsAppUrl }}" target="_blank" rel="noopener noreferrer">
                                <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Message us on WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade portfolio-lightbox" id="portfolio-lightbox" tabindex="-1"
        aria-labelledby="portfolio-lightbox-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h2 class="modal-title fs-6" id="portfolio-lightbox-title"></h2>
                    <button class="btn-close portfolio-lightbox-close" type="button" data-bs-dismiss="modal" aria-label="Close image viewer"></button>
                </div>
                <div class="modal-body position-relative p-0">
                    <button class="portfolio-lightbox-control portfolio-lightbox-previous" type="button" data-lightbox-previous aria-label="Previous image">
                        <span class="bi bi-chevron-left" aria-hidden="true"></span>
                    </button>
                    <div class="portfolio-lightbox-image-wrap">
                        <img class="portfolio-lightbox-image" src="" alt="" data-lightbox-image>
                    </div>
                    <button class="portfolio-lightbox-control portfolio-lightbox-next" type="button" data-lightbox-next aria-label="Next image">
                        <span class="bi bi-chevron-right" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="modal-footer justify-content-between border-0">
                    <span class="small text-white-50" data-lightbox-count></span>
                    <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="#" target="_blank" rel="noopener noreferrer" data-lightbox-preview hidden>
                        <span data-lightbox-preview-label>View demo</span>
                        <span class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const modalElement = document.getElementById('portfolio-lightbox');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const image = modalElement.querySelector('[data-lightbox-image]');
        const title = modalElement.querySelector('#portfolio-lightbox-title');
        const count = modalElement.querySelector('[data-lightbox-count]');
        const previewLink = modalElement.querySelector('[data-lightbox-preview]');
        const previewLabel = modalElement.querySelector('[data-lightbox-preview-label]');
        const previous = modalElement.querySelector('[data-lightbox-previous]');
        const next = modalElement.querySelector('[data-lightbox-next]');
        let images = [];
        let activeIndex = 0;

        const renderImage = () => {
            image.src = images[activeIndex];
            image.alt = `${title.textContent}, image ${activeIndex + 1}`;
            count.textContent = `${activeIndex + 1} of ${images.length}`;
            previous.hidden = images.length < 2;
            next.hidden = images.length < 2;
        };

        document.querySelectorAll('[data-portfolio-lightbox]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                images = JSON.parse(trigger.dataset.gallery);
                activeIndex = 0;
                title.textContent = trigger.dataset.galleryTitle || 'Website preview';
                previewLink.href = trigger.dataset.previewUrl || '#';
                previewLink.hidden = !trigger.dataset.previewUrl;
                previewLabel.textContent = trigger.dataset.previewLabel || 'View website';
                renderImage();
                modal.show();
            });
        });

        previous.addEventListener('click', () => {
            activeIndex = (activeIndex - 1 + images.length) % images.length;
            renderImage();
        });
        next.addEventListener('click', () => {
            activeIndex = (activeIndex + 1) % images.length;
            renderImage();
        });
        modalElement.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft' && images.length > 1) previous.click();
            if (event.key === 'ArrowRight' && images.length > 1) next.click();
        });
        modalElement.addEventListener('hidden.bs.modal', () => {
            image.removeAttribute('src');
            image.alt = '';
            previewLink.removeAttribute('href');
            previewLink.hidden = true;
        });
    })();
</script>
@endpush
@endsection
