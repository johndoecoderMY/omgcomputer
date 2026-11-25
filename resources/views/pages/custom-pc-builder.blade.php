@extends('layouts.guest')

@section('title', __('Custom PC Builder'))
@section('meta_description', __('Preview a custom PC case without signing in, then tell OMG Computer your budget and needs to get a compatible custom build quote.'))
@section('og_image', asset('assets/img/omg/ready-built-pc.jpg'))
@section('og_image_alt', 'Custom desktop PC with illuminated internal components')

@push('styles')
    <style>
        .pc-builder-hero {
            background:
                radial-gradient(circle at 85% 20%, rgba(243, 67, 3, .08), transparent 28rem),
                linear-gradient(180deg, #fff 0%, #fffaf7 100%);
        }

        [data-bs-theme="dark"] .pc-builder-hero {
            background:
                radial-gradient(circle at 85% 20%, rgba(243, 67, 3, .16), transparent 28rem),
                linear-gradient(180deg, #0d1523 0%, #17110f 100%);
        }

        .pc-builder-kicker {
            color: #b83400;
            letter-spacing: .12em;
        }

        .pc-builder-scene {
            --case-color: #222b37;
            position: relative;
            min-height: 28rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 1.25rem;
            background:
                radial-gradient(ellipse at 50% 80%, rgba(243, 67, 3, .22), transparent 48%),
                radial-gradient(ellipse at 60% 35%, rgba(35, 111, 153, .2), transparent 42%),
                linear-gradient(145deg, #111b2b, #070b12 72%);
            box-shadow: 0 1.5rem 4rem rgba(12, 21, 34, .2);
        }

        .pc-builder-scene[data-case-color="white"] {
            --case-color: #dce3ec;
        }

        .pc-builder-canvas,
        .pc-builder-fallback {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .pc-builder-canvas {
            z-index: 1;
            opacity: 0;
            touch-action: none;
            transition: opacity .25s ease;
        }

        .pc-builder-scene[data-ready="true"] .pc-builder-canvas {
            opacity: 1;
        }

        .pc-builder-fallback {
            display: grid;
            place-items: center;
            transition: opacity .25s ease;
        }

        .pc-builder-scene[data-ready="true"] .pc-builder-fallback {
            opacity: 0;
        }

        .pc-builder-fallback-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 1.5rem 2rem rgba(0,0,0,.5));
        }

        .pc-builder-scene-caption {
            position: absolute;
            z-index: 2;
            right: 1rem;
            bottom: 5.2rem;
            left: 1rem;
            color: rgba(255,255,255,.72);
            font-size: .8rem;
            pointer-events: none;
        }

        .pc-builder-scene-caption a {
            pointer-events: auto;
        }

        .pc-builder-scene-controls {
            position: absolute;
            z-index: 3;
            right: 1rem;
            bottom: 1rem;
            left: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            color: #fff;
        }

        .pc-builder-case-options {
            display: flex;
            gap: .4rem;
        }

        .pc-builder-case-option {
            border: 1px solid rgba(255,255,255,.48);
            border-radius: 999px;
            padding: .3rem .7rem;
            background: rgba(8,14,24,.76);
            color: #fff;
            font-size: .75rem;
        }

        .pc-builder-case-option[aria-pressed="true"] {
            border-color: #ffb51b;
            box-shadow: 0 0 0 1px #ffb51b;
        }

        .pc-builder-feature-icon {
            display: inline-grid;
            width: 2.75rem;
            height: 2.75rem;
            place-items: center;
            border-radius: .8rem;
            background: rgba(243, 67, 3, .1);
            color: #b83400;
            font-size: 1.25rem;
        }

        [data-bs-theme="dark"] .pc-builder-kicker,
        [data-bs-theme="dark"] .pc-builder-feature-icon {
            color: #ff9a62;
        }

        [data-bs-theme="dark"] .pc-builder-feature-icon {
            background: rgba(243, 67, 3, .16);
        }

        [data-bs-theme="dark"] #build-process {
            background: #111827 !important;
        }

        [data-bs-theme="dark"] .pc-builder-scene {
            border-color: rgba(255, 255, 255, .1);
            box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .38);
        }

        @media (max-width: 575.98px) {
            .pc-builder-scene { min-height: 23rem; }
            .pc-builder-scene-controls { align-items: flex-start; flex-direction: column; }
            .pc-builder-scene-caption { bottom: 6rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .pc-builder-canvas,
            .pc-builder-fallback { transition: none; }
        }
    </style>
@endpush

@section('content')
    @php
        $customPcQuoteMessage = rawurlencode(
            "Hi OMG Computer, I'd like a custom PC quote.\nBudget (RM): __\nMain use, games, or apps: __\nMonitor resolution: __\nParts I already own: __\nPreferred case size or colour (optional): __\nPlease recommend a compatible build."
        );
        $customPcQuoteUrl = 'https://wa.me/60104224350?text='.$customPcQuoteMessage;
    @endphp
    <section class="pc-builder-hero py-4 py-lg-5">
        <div class="container py-lg-3">
            <div class="row align-items-center g-4 g-xl-5">
                <div class="col-lg-5">
                    <p class="pc-builder-kicker small fw-bold text-uppercase mb-3">{{ __('Custom desktop builds') }}</p>
                    <h1 class="display-5 fw-bold text-1000 mb-3">{{ __('Preview your PC style. We’ll help plan the build.') }}</h1>
                    <p class="lead text-700 mb-4">{{ __('Explore the case preview without signing in. It’s a visual sample, not a live part selector. Tell us your budget and what you need the PC for; we’ll recommend compatible parts.') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary rounded-pill px-4" href="{{ $customPcQuoteUrl }}" target="_blank" rel="noopener noreferrer">{{ __('Request a quote on WhatsApp') }}</a>
                        <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('shop.public', ['type' => 'ready_built_pc']) }}">{{ __('Browse ready-built PCs') }}</a>
                    </div>
                    <p class="small text-700 mt-3 mb-0">{{ __('No component knowledge is needed. Parts, availability, and final pricing are confirmed before you approve a build.') }}</p>
                </div>
                <div class="col-lg-7">
                    <div class="pc-builder-scene" data-pc-visual data-case-color="graphite" data-ready="false">
                        <div class="pc-builder-fallback" aria-hidden="true">
                            <img class="pc-builder-fallback-image" src="{{ asset('assets/img/omg/ready-built-pc-900.webp') }}" alt="">
                        </div>
                        <canvas class="pc-builder-canvas" data-pc-canvas tabindex="0" aria-label="{{ __('Interactive 3D custom PC preview. Drag to rotate, scroll or pinch to zoom, and use the arrow keys to rotate.') }}"></canvas>
                        <p class="pc-builder-scene-caption mb-0">
                            <span class="bi bi-arrows-move me-1" aria-hidden="true"></span>{{ __('Interactive 3D preview · drag to rotate · scroll or pinch to zoom') }}
                            <a class="text-white-50 ms-2" href="{{ asset('licenses/threejs-MIT.txt') }}">{{ __('Three.js license') }}</a>
                        </p>
                        <div class="pc-builder-scene-controls">
                            <span class="small">{{ __('Choose a case finish') }}</span>
                            <div class="pc-builder-case-options" role="group" aria-label="{{ __('Case finish') }}">
                                <button class="pc-builder-case-option" type="button" data-case-choice="graphite" aria-pressed="true">{{ __('Graphite') }}</button>
                                <button class="pc-builder-case-option" type="button" data-case-choice="white" aria-pressed="false">{{ __('White') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4 py-lg-5">
        <div class="container">
            <div class="text-center mx-auto mb-4 mb-lg-5" style="max-width: 42rem;">
                <p class="small fw-bold text-primary text-uppercase mb-2">{{ __('Start with your priorities') }}</p>
                <h2 class="h1 fw-bold text-1000 mb-3">{{ __('What should your PC be great at?') }}</h2>
                <p class="text-700 mb-0">{{ __('Tell us how you’ll use it. We’ll help balance performance, upgrade options, and budget without guessing at a one-size-fits-all spec.') }}</p>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="pc-builder-feature-icon bi bi-controller mb-3" aria-hidden="true"></span>
                            <h3 class="h5 fw-bold text-1000">{{ __('Gaming') }}</h3>
                            <p class="text-700 mb-0">{{ __('Share the games and display resolution you care about so we can discuss a suitable graphics and cooling setup.') }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="pc-builder-feature-icon bi bi-camera-reels mb-3" aria-hidden="true"></span>
                            <h3 class="h5 fw-bold text-1000">{{ __('Creative work') }}</h3>
                            <p class="text-700 mb-0">{{ __('For editing, design, 3D work, and streaming, tell us which apps and projects should guide component choices.') }}</p>
                        </div>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="pc-builder-feature-icon bi bi-briefcase mb-3" aria-hidden="true"></span>
                            <h3 class="h5 fw-bold text-1000">{{ __('Work and everyday') }}</h3>
                            <p class="text-700 mb-0">{{ __('Plan around the software you use, multitasking, storage needs, desk space, and room for future upgrades.') }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4 py-lg-5 bg-light" id="build-process">
        <div class="container">
            <div class="row align-items-start g-4">
                <div class="col-lg-5">
                    <p class="small fw-bold text-primary text-uppercase mb-2">{{ __('A clear process') }}</p>
                    <h2 class="h1 fw-bold text-1000 mb-3">{{ __('A few details are enough to get started.') }}</h2>
                    <p class="text-700 mb-0">{{ __('You don’t need to know component names or choose every part. A short message about your needs is enough for us to make a recommendation.') }}</p>
                </div>
                <div class="col-lg-7">
                    <div class="d-grid gap-3">
                        <div class="d-flex gap-3">
                            <span class="badge rounded-pill text-bg-primary align-self-start">1</span>
                            <div><h3 class="h6 fw-bold text-1000 mb-1">{{ __('Tell us your budget and what you do') }}</h3><p class="text-700 mb-0">{{ __('Mention games or apps, monitor resolution, and any parts you want to reuse. If you’re unsure, just describe what you need the PC to do.') }}</p></div>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="badge rounded-pill text-bg-primary align-self-start">2</span>
                            <div><h3 class="h6 fw-bold text-1000 mb-1">{{ __('Review our recommendation and quote') }}</h3><p class="text-700 mb-0">{{ __('We’ll confirm compatible parts, availability, and pricing for your proposed build.') }}</p></div>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="badge rounded-pill text-bg-primary align-self-start">3</span>
                            <div><h3 class="h6 fw-bold text-1000 mb-1">{{ __('Approve before work starts') }}</h3><p class="text-700 mb-0">{{ __('We proceed only after you’ve reviewed and approved the agreed build and quote.') }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 mt-lg-5">
                <a class="btn btn-primary rounded-pill px-4" href="{{ $customPcQuoteUrl }}" target="_blank" rel="noopener noreferrer">{{ __('Request a quote on WhatsApp') }}</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/custom-pc-builder-visual.js')
@endpush
