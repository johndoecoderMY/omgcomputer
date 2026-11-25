@extends('layouts.guest')

@section('title', 'About OMG Computer')
@section('meta_description', 'Meet OMG Computer: practical computer repair, upgrades, custom PCs, and website services for home and business users, with clear advice and friendly support.')
@section('og_image', asset('assets/img/omg/repair-board-work-900.webp'))
@section('og_image_alt', 'A close-up of computer repair work at OMG Computer.')

@section('content')
    @php
        $aboutWhatsappUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, I would like to ask about your services.');
    @endphp

    @push('styles')
        <style>
            .about-page .about-mosaic {
                display: grid;
                grid-template-columns: repeat(12, minmax(0, 1fr));
                grid-template-rows: repeat(2, minmax(150px, 210px));
                gap: 1rem;
            }
            .about-page .about-mosaic-item {
                min-width: 0;
                overflow: hidden;
                border-radius: 1.25rem;
                background: var(--falcon-gray-100, #f1f5f9);
            }
            .about-page .about-mosaic-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .about-page .about-mosaic-main { grid-column: span 6; grid-row: span 2; }
            .about-page .about-mosaic-small { grid-column: span 3; }
            .about-page .about-intro { max-width: 760px; }
            .about-page .about-copy { max-width: 540px; }
            .about-page .about-service-image {
                aspect-ratio: 4 / 3;
                object-fit: cover;
            }
            .about-page .about-principle-icon {
                display: grid;
                place-items: center;
                width: 4rem;
                height: 4rem;
                border-radius: 1rem;
                color: var(--falcon-primary);
                background: rgba(var(--falcon-primary-rgb), .08);
                font-size: 1.7rem;
            }
            .about-page .about-step-number {
                display: grid;
                place-items: center;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 auto;
                border-radius: 50%;
                color: #fff;
                background: var(--falcon-primary);
                font-weight: 700;
            }
            .about-page .about-company-section { background: #162b42; }
            .about-page .about-company-section .text-1000 { color: #fff !important; }
            .about-page .about-company-section .text-700 { color: #c2d0e0 !important; }
            .about-page .about-company-actions {
                display: flex;
                flex-direction: column;
                gap: .75rem;
                width: 22rem;
                max-width: 100%;
            }
            .about-page .about-cta {
                background: var(--falcon-body-bg, #fff);
            }
            .about-page .about-cta-copy { color: var(--falcon-gray-700, #5e6e82); }
            @media (max-width: 767.98px) {
                .about-page .about-mosaic {
                    grid-template-rows: repeat(2, minmax(110px, 150px));
                    gap: .65rem;
                }
                .about-page .about-mosaic-main { grid-column: span 7; }
                .about-page .about-mosaic-small { grid-column: span 5; }
            }
            @media (max-width: 575.98px) {
                .about-page .about-mosaic {
                    grid-template-rows: repeat(2, minmax(90px, 120px));
                    gap: .5rem;
                }
                .about-page .about-mosaic-item { border-radius: .85rem; }
            }
        </style>
    @endpush

    <div class="about-page">
        <section class="pt-2 pb-4 pb-lg-5" aria-label="{{ __('A look at our technology services') }}">
            <div class="container">
                <div class="about-mosaic">
                    <div class="about-mosaic-item about-mosaic-main">
                        <img src="{{ asset('assets/img/omg/hero-technician-768.webp') }}" alt="{{ __('A technician working with a laptop at an electronics workbench') }}" width="768" height="439" fetchpriority="high">
                    </div>
                    <div class="about-mosaic-item about-mosaic-small">
                        <img src="{{ asset('assets/img/omg/repair-board-work-720.webp') }}" alt="{{ __('Close-up of a technician inspecting an electronic circuit board') }}" width="720" height="480" loading="lazy">
                    </div>
                    <div class="about-mosaic-item about-mosaic-small">
                        <img src="{{ asset('assets/img/omg/website-design-work-720.webp') }}" alt="{{ __('Website design and development work on a laptop') }}" width="720" height="480" loading="lazy">
                    </div>
                    <div class="about-mosaic-item about-mosaic-small">
                        <img src="{{ asset('assets/img/omg/ready-built-pc-720.webp') }}" alt="{{ __('A ready-built desktop computer with illuminated components') }}" width="720" height="480" loading="lazy">
                    </div>
                    <div class="about-mosaic-item about-mosaic-small">
                        <img src="{{ asset('assets/img/omg/repair-pc-components-720.webp') }}" alt="{{ __('Computer components being checked during a repair') }}" width="720" height="480" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 py-lg-7">
            <div class="container">
                <div class="about-intro mx-auto">
                    <span class="text-primary fw-bold text-uppercase fs-10">{{ __('A little about us') }}</span>
                    <h1 class="display-4 fw-bold text-1000 mt-2 mb-4">{{ __('Technology should make life easier.') }}</h1>
                    <div class="about-copy">
                        <p class="lead text-700">{{ __('At OMG Computer, we help people and businesses solve everyday technology problems and bring new ideas to life.') }}</p>
                        <p class="text-700">{{ __('From computer troubleshooting and upgrades to ready-built PCs and custom websites, our focus is on practical solutions, clear advice, and friendly local support.') }}</p>
                        <p class="text-700 mb-4">{{ __('We take time to understand what you need, explain the options in plain language, and help you choose a sensible next step—without the jargon or pressure.') }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary rounded-pill px-4" href="{{ route('bookings.create') }}">{{ __('Book a service') }} <span class="bi bi-arrow-right ms-1" aria-hidden="true"></span></a>
                            <a class="btn btn-outline-primary rounded-pill px-4" href="{{ $aboutWhatsappUrl }}" target="_blank" rel="noopener noreferrer">{{ __('Talk to us') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-6 py-lg-7 bg-body-tertiary">
            <div class="container">
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <span class="text-primary fw-bold text-uppercase fs-10">{{ __('What we do') }}</span>
                        <h2 class="fw-bold text-1000 mt-2">{{ __('Technology help for home and work.') }}</h2>
                        <p class="text-700 mb-0">{{ __('One place to get help with your computer, find a PC, or plan a website project.') }}</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">
                            <img class="about-service-image card-img-top" src="{{ asset('assets/img/omg/repair-board-work-720.webp') }}" alt="{{ __('Computer repair and circuit board inspection') }}" width="720" height="480" loading="lazy">
                            <div class="card-body p-4">
                                <span class="bi bi-tools text-primary fs-4" aria-hidden="true"></span>
                                <h3 class="h5 text-1000 mt-3">{{ __('Computer repair & upgrades') }}</h3>
                                <p class="text-700 mb-0">{{ __('Get a clear diagnosis and practical options for troubleshooting, repairs, performance upgrades, and software support.') }}</p>
                            </div>
                            <div class="card-footer bg-body border-0 px-4 pb-4">
                                <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="{{ route('bookings.create') }}">{{ __('Book a service') }}</a>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">
                            <img class="about-service-image card-img-top" src="{{ asset('assets/img/omg/ready-built-pc-720.webp') }}" alt="{{ __('Ready-built desktop computer') }}" width="720" height="480" loading="lazy">
                            <div class="card-body p-4">
                                <span class="bi bi-pc-display-horizontal text-primary fs-4" aria-hidden="true"></span>
                                <h3 class="h5 text-1000 mt-3">{{ __('PCs & components') }}</h3>
                                <p class="text-700 mb-0">{{ __('Browse available parts and ready-built computers, with choices for different needs and budgets.') }}</p>
                            </div>
                            <div class="card-footer bg-body border-0 px-4 pb-4">
                                <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="{{ route('shop.public') }}">{{ __('Browse the shop') }}</a>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">
                            <img class="about-service-image card-img-top" src="{{ asset('assets/img/omg/website-design-work-720.webp') }}" alt="{{ __('Website design and development work on a laptop') }}" width="720" height="480" loading="lazy">
                            <div class="card-body p-4">
                                <span class="bi bi-window text-primary fs-4" aria-hidden="true"></span>
                                <h3 class="h5 text-1000 mt-3">{{ __('Websites & online shops') }}</h3>
                                <p class="text-700 mb-0">{{ __('Plan a custom website or online shop around your goals, audience, and project requirements.') }}</p>
                            </div>
                            <div class="card-footer bg-body border-0 px-4 pb-4">
                                <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="{{ route('website-previews.index') }}">{{ __('View website previews') }}</a>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-6 py-lg-7">
            <div class="container">
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <span class="text-primary fw-bold text-uppercase fs-10">{{ __('What matters to us') }}</span>
                        <h2 class="fw-bold text-1000 mt-2">{{ __('Good support starts with trust.') }}</h2>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <article class="card h-100 border-0 bg-body-tertiary p-3 p-lg-4">
                            <div class="about-principle-icon mb-4"><span class="bi bi-chat-square-text" aria-hidden="true"></span></div>
                            <h3 class="h5 text-1000">{{ __('Clear advice') }}</h3>
                            <p class="small text-700 mb-0">{{ __('We explain what we find and what your options mean, in straightforward language.') }}</p>
                        </article>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <article class="card h-100 border-0 bg-body-tertiary p-3 p-lg-4">
                            <div class="about-principle-icon mb-4"><span class="bi bi-lightbulb" aria-hidden="true"></span></div>
                            <h3 class="h5 text-1000">{{ __('Practical solutions') }}</h3>
                            <p class="small text-700 mb-0">{{ __('Recommendations are shaped around the problem, your priorities, and your budget.') }}</p>
                        </article>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <article class="card h-100 border-0 bg-body-tertiary p-3 p-lg-4">
                            <div class="about-principle-icon mb-4"><span class="bi bi-check2-circle" aria-hidden="true"></span></div>
                            <h3 class="h5 text-1000">{{ __('Your approval first') }}</h3>
                            <p class="small text-700 mb-0">{{ __('For repairs, we share the diagnosis and quote and get your approval before carrying out the agreed work.') }}</p>
                        </article>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <article class="card h-100 border-0 bg-body-tertiary p-3 p-lg-4">
                            <div class="about-principle-icon mb-4"><span class="bi bi-heart" aria-hidden="true"></span></div>
                            <h3 class="h5 text-1000">{{ __('Friendly support') }}</h3>
                            <p class="small text-700 mb-0">{{ __('We welcome questions from home users, students, and businesses—whether you know the technical terms or not.') }}</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-6 py-lg-7 bg-body-tertiary">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-4">
                        <span class="text-primary fw-bold text-uppercase fs-10">{{ __('How we work') }}</span>
                        <h2 class="fw-bold text-1000 mt-2">{{ __('A straightforward way forward.') }}</h2>
                        <p class="text-700 mb-0">{{ __('Start with a conversation. We’ll help you understand the next step before you decide how to proceed.') }}</p>
                    </div>
                    <div class="col-lg-8">
                        <div class="d-flex gap-3 mb-4">
                            <span class="about-step-number">1</span>
                            <div>
                                <h3 class="h5 text-1000 mb-1">{{ __('Tell us what you need') }}</h3>
                                <p class="text-700 mb-0">{{ __('Describe the computer issue or the project you have in mind. A few details are enough to get started.') }}</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-4">
                            <span class="about-step-number">2</span>
                            <div>
                                <h3 class="h5 text-1000 mb-1">{{ __('Understand your options') }}</h3>
                                <p class="text-700 mb-0">{{ __('We’ll discuss what may help, explain any assessment or project costs, and outline a suitable plan.') }}</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="about-step-number">3</span>
                            <div>
                                <h3 class="h5 text-1000 mb-1">{{ __('Choose the next step') }}</h3>
                                <p class="text-700 mb-0">{{ __('For repairs, we’ll wait for your approval of the written quote before proceeding. Website projects are quoted based on their requirements.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($companyProfile->legal_name || $companyProfile->ssm_registration_number)
            <section class="about-company-section py-5">
                <div class="container">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                        <div>
                            <span class="text-primary fw-bold text-uppercase fs-10">{{ __('Company details') }}</span>
                            <h2 class="h5 text-1000 mt-2 mb-1">{{ $companyProfile->legal_name ?: 'OMG Computer' }}</h2>
                            <p class="small text-700 mb-1">{{ __('OMG Computer is owned by OMG Empire.') }}</p>
                            @if ($companyProfile->ssm_registration_number)
                                <p class="small text-700 mb-0">{{ __('SSM registration no.') }}: {{ $companyProfile->ssm_registration_number }}</p>
                            @endif
                        </div>
                        <div class="about-company-actions">
                            <a class="btn btn-primary rounded-pill px-4" href="mailto:support@omgcomputer.my">
                                <span class="bi bi-envelope me-2" aria-hidden="true"></span>support@omgcomputer.my
                            </a>
                            <a class="btn btn-success rounded-pill px-4" href="{{ $aboutWhatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>{{ __('Message us on WhatsApp') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="about-cta py-6 py-lg-7">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="text-primary fw-bold text-uppercase fs-10">{{ __('Let’s get started') }}</span>
                        <h2 class="h1 fw-bold text-1000 mt-2 mb-3">{{ __('Have a question or a project in mind?') }}</h2>
                        <p class="about-cta-copy lead mb-0">{{ __('Tell us what you need help with. We’ll help you work out a sensible next step.') }}</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <a class="btn btn-primary rounded-pill px-4" href="{{ route('bookings.create') }}">{{ __('Book a service') }}</a>
                            <a class="btn btn-outline-light rounded-pill px-4" href="{{ $aboutWhatsappUrl }}" target="_blank" rel="noopener noreferrer"><span class="bi bi-whatsapp me-2" aria-hidden="true"></span>{{ __('WhatsApp us') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
