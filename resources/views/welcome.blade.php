@extends('layouts.guest')

@section('title', 'Computer Repair, Website Design & Development | OMG Computer')
@section('meta_description', 'Computer repair, upgrades, custom PCs, and website design in Malaysia. Get practical technology support and book a service with OMG Computer.')

@section('content')
    @php
        $whatsappContactUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, I would like to ask about your services.');
        $websiteQuoteUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, I am interested in website design and development. I would like to discuss a project and get a quote.');
        $configuredContactEmail = config('mail.from.address');
        $contactEmail = is_string($companyProfile->email) && filter_var($companyProfile->email, FILTER_VALIDATE_EMAIL)
            ? $companyProfile->email
            : (
                is_string($configuredContactEmail)
                && filter_var($configuredContactEmail, FILTER_VALIDATE_EMAIL)
                && $configuredContactEmail !== 'hello@example.com'
                    ? $configuredContactEmail
                    : 'hello@omgcomputer.my'
            );
    @endphp
    @push('styles')
    <style>
        .landing-hero { min-height: 720px; padding-top: 118px; background: linear-gradient(135deg, var(--falcon-gray-100, #fff) 0%, var(--falcon-body-bg, #edf2f9) 100%); overflow: hidden; }
        .landing-hero .hero-title { max-width: 760px; color: var(--falcon-gray-1000, #172033); font-size: clamp(3rem, 6vw, 5.6rem); line-height: .96; letter-spacing: -.055em; }
        .landing-hero .hero-title span { color: var(--falcon-primary); }
        .landing-hero .hero-kicker { display: inline-flex; align-items: flex-start; max-width: 100%; color: var(--falcon-primary); background: rgba(var(--falcon-primary-rgb), .08); border: 1px solid rgba(var(--falcon-primary-rgb), .18); white-space: normal; line-height: 1.4; }
        .landing-hero .hero-kicker .bi { flex-shrink: 0; }
        .landing-hero .hero-kicker-copy { min-width: 0; overflow-wrap: anywhere; }
        .landing-hero .hero-copy { max-width: 590px; }
        .landing-hero .hero-primary-button { color: #fff; background: var(--falcon-primary); border-color: var(--falcon-primary); box-shadow: 0 12px 30px rgba(var(--falcon-primary-rgb), .22); }
        .landing-hero .hero-primary-button:hover { filter: brightness(.92); }
        .track-repair-button {
            --falcon-btn-color: #172033;
            --falcon-btn-bg: #ffb51b;
            --falcon-btn-border-color: #ffb51b;
            --falcon-btn-hover-color: #172033;
            --falcon-btn-hover-bg: #e6a317;
            --falcon-btn-hover-border-color: #e6a317;
            --falcon-btn-focus-shadow-rgb: 255, 181, 27;
            --falcon-btn-active-color: #172033;
            --falcon-btn-active-bg: #cc9015;
            --falcon-btn-active-border-color: #cc9015;
        }
        [data-bs-theme="dark"] .landing-hero .hero-shop-button {
            --falcon-btn-color: var(--falcon-gray-100);
            --falcon-btn-bg: var(--falcon-gray-900);
            --falcon-btn-border-color: var(--falcon-gray-900);
            --falcon-btn-focus-shadow-rgb: 157, 169, 187;
            --falcon-btn-hover-color: var(--falcon-gray-100);
            --falcon-btn-hover-bg: var(--falcon-gray-800);
            --falcon-btn-hover-border-color: var(--falcon-gray-800);
            --falcon-btn-active-color: var(--falcon-gray-100);
            --falcon-btn-active-bg: var(--falcon-gray-700);
            --falcon-btn-active-border-color: var(--falcon-gray-700);
            --falcon-btn-disabled-color: var(--falcon-gray-100);
            --falcon-btn-disabled-bg: var(--falcon-gray-900);
            --falcon-btn-disabled-border-color: var(--falcon-gray-900);
        }
        .landing-hero .hero-visual { position: relative; }
        .landing-hero .hero-image { border-radius: 28px; ; box-shadow: 0 28px 70px rgba(23,32,51,.16); }
        .landing-hero .hero-float { position: absolute; z-index: 2; display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,.96); border: 1px solid rgba(23,32,51,.08); border-radius: 16px; box-shadow: 0 16px 40px rgba(23,32,51,.14); }
        .landing-hero .hero-float-one { left: -24px; top: 18%; }
        .landing-hero .hero-float-two { right: -12px; bottom: 12%; }
        .landing-hero .hero-float-icon { width: 52px; height: 52px; display: grid; place-items: center; flex-shrink: 0; border-radius: 15px; color: var(--falcon-primary); background: rgba(var(--falcon-primary-rgb), .08); }
        .bi { min-width: 1em; }
        @media (max-width: 991.98px) { .landing-hero { min-height: auto; padding-top: 130px; } .landing-hero .hero-float-one { left: 0; } .landing-hero .hero-float-two { right: 0; } }
        @media (max-width: 575.98px) { .landing-hero { padding-top: 112px; } .landing-hero .hero-title { letter-spacing: -.04em; } .landing-hero .hero-actions .btn { width: 100%; } .landing-hero .hero-float { max-width: 210px; padding: .65rem !important; } }
        .landing-contact { background: #0b1727; }
        .landing-contact .contact-kicker, .landing-contact .contact-icon { color: #ff4b0b; }
        .landing-contact .contact-copy { color: #a9bdd5; }
        .landing-contact .contact-card { background: #111f30; }
        .landing-contact .contact-outline-button { color: #ff4b0b; border-color: #ff4b0b; }
        .landing-contact .contact-outline-button:hover { color: #fff; background: #ff4b0b; }
        .landing-how-it-works { background: #0b1727; }
        .landing-how-it-works .text-1000 { color: #fff !important; }
        .landing-how-it-works .text-700 { color: #a9bdd5 !important; }
    </style>
    @endpush

    <section class="landing-hero d-flex align-items-center" id="home">
        <div class="container">
            <div class="row align-items-center g-5 py-5">
                <div class="col-lg-6">
                    <span class="hero-kicker badge rounded-pill mb-4 px-3 py-2">
                        <span class="bi bi-tools me-2" aria-hidden="true"></span>
                        <span class="hero-kicker-copy">{{ __('Computer repair, website design & technology support') }}</span>
                    </span>
                    <h1 class="hero-title fw-bold mb-4">{{ __('Computer trouble?') }}<br><span>{{ __('We’ll fix it.') }}</span></h1>
                    <p class="hero-copy lead text-700 mb-4">{{ __('Reliable repairs, upgrades and custom websites — delivered with clear advice and friendly local support.') }}</p>
                    <div class="hero-actions row g-3">
                        <div class="col-12 col-md-6">
                            <a class="hero-primary-button btn btn-lg rounded-pill px-3 w-100" href="{{ route('bookings.create') }}">
                                <span class="bi bi-calendar-check me-2" aria-hidden="true"></span>{{ __('Book a service') }}
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a class="btn btn-success btn-lg rounded-pill px-3 w-100" href="{{ $whatsappContactUrl }}" target="_blank" rel="noopener noreferrer">
                                <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>{{ __('WhatsApp us') }}
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a class="hero-shop-button btn btn-dark btn-lg rounded-pill px-3 w-100" href="{{ route('shop.public') }}">
                                <span class="bi bi-bag me-2" aria-hidden="true"></span>Shop
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a class="track-repair-button btn btn-lg rounded-pill px-3 w-100" href="{{ route('repairs.track.form') }}">
                                <span class="bi bi-search me-2" aria-hidden="true"></span>{{ __('Track your repair') }}
                            </a>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-5 text-700 small">
                        <span><span class="bi bi-check-circle-fill text-success me-2"></span>{{ __('Clear diagnosis') }}</span>
                        <span><span class="bi bi-check-circle-fill text-success me-2"></span>{{ __('Practical solutions') }}</span>
                        <span><span class="bi bi-check-circle-fill text-success me-2"></span>{{ __('Friendly support') }}</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual">
                        <picture class="d-block">
                            <source type="image/webp" srcset="{{ asset('assets/img/omg/hero-technician-640.webp') }} 640w, {{ asset('assets/img/omg/hero-technician-768.webp') }} 768w, {{ asset('assets/img/omg/hero-technician-960.webp') }} 960w, {{ asset('assets/img/omg/hero-technician-1400.webp') }} 1400w" sizes="(max-width: 991.98px) 100vw, 50vw">
                            <img class="hero-image img-fluid w-100" src="{{ asset('assets/img/omg/hero-technician.jpg') }}" alt="Person working with a laptop at an electronics workbench" width="1400" height="800" fetchpriority="high" decoding="async" sizes="(max-width: 991.98px) 100vw, 50vw">
                        </picture>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7 bg-body-tertiary" id="services">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-bold text-uppercase fs-10">{{ __('How we can help') }}</span>
                    <h2 class="fw-bold text-1000 mt-2">{{ __('Technology services for home and work') }}</h2>
                    <p class="text-700 mb-0">{{ __('From computer support to custom websites, get practical technology help for home and work.') }}</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <picture class="d-block">
                            <source type="image/webp" srcset="{{ asset('assets/img/omg/repair-board-work-480.webp') }} 480w, {{ asset('assets/img/omg/repair-board-work-720.webp') }} 720w, {{ asset('assets/img/omg/repair-board-work-768.webp') }} 768w, {{ asset('assets/img/omg/repair-board-work-900.webp') }} 900w" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw">
                            <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/repair-board-work.jpg') }}" alt="{{ __('Close-up of a technician inspecting an electronic circuit board') }}" width="900" height="600" loading="lazy" decoding="async" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        </picture>
                        <div class="card-body p-4">
                            <span class="bi bi-activity text-primary fs-4 mb-3"></span>
                            <h3 class="h5 text-1000">{{ __('PC diagnosis & repair') }}</h3>
                            <p class="text-700 mb-0">{{ __('Troubleshoot crashes, slowdowns, startup issues, and hardware faults. Get a clear diagnosis and repair options.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <picture class="d-block">
                            <source type="image/webp" srcset="{{ asset('assets/img/omg/repair-pc-components-480.webp') }} 480w, {{ asset('assets/img/omg/repair-pc-components-720.webp') }} 720w, {{ asset('assets/img/omg/repair-pc-components-768.webp') }} 768w, {{ asset('assets/img/omg/repair-pc-components-900.webp') }} 900w" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw">
                            <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/repair-pc-components.jpg') }}" alt="{{ __('Close-up view of PC cooling and internal components') }}" width="900" height="600" loading="lazy" decoding="async" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        </picture>
                        <div class="card-body p-4">
                            <span class="bi bi-memory text-primary fs-4 mb-3"></span>
                            <h3 class="h5 text-1000">{{ __('Upgrades & software support') }}</h3>
                            <p class="text-700 mb-0">{{ __('Improve performance, resolve software issues, and get practical help with system and security updates.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <picture class="d-block">
                            <source type="image/webp" srcset="{{ asset('assets/img/omg/website-design-work-480.webp') }} 480w, {{ asset('assets/img/omg/website-design-work-720.webp') }} 720w, {{ asset('assets/img/omg/website-design-work-768.webp') }} 768w, {{ asset('assets/img/omg/website-design-work-900.webp') }} 900w" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw">
                            <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/website-design-work.jpg') }}" alt="{{ __('Laptop displaying website code at a desk') }}" width="900" height="600" loading="lazy" decoding="async" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        </picture>
                        <div class="card-body p-4">
                            <span class="bi bi-globe text-primary fs-4 mb-3"></span>
                            <h3 class="h5 text-1000">{{ __('Website design & development') }}</h3>
                            <p class="text-700">{{ __('Get a custom website designed and built for your business or organization.') }}</p>
                            <a class="btn btn-sm btn-primary rounded-pill" href="#website-pricing">
                                {{ __('Explore website packages') }} <span class="bi bi-arrow-down ms-1" aria-hidden="true"></span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <picture class="d-block">
                            <source type="image/webp" srcset="{{ asset('assets/img/omg/ready-built-pc-480.webp') }} 480w, {{ asset('assets/img/omg/ready-built-pc-720.webp') }} 720w, {{ asset('assets/img/omg/ready-built-pc-768.webp') }} 768w, {{ asset('assets/img/omg/ready-built-pc-900.webp') }} 900w" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw">
                            <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/ready-built-pc.jpg') }}" alt="{{ __('Example of a ready-built desktop PC with illuminated internal components') }}" width="900" height="600" loading="lazy" decoding="async" sizes="(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 25vw" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        </picture>
                        <div class="card-body p-4">
                            <span class="bi bi-pc-display-horizontal text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h3 class="h5 text-1000">{{ __('Ready-built custom PCs') }}</h3>
                            <p class="text-700">{{ __('Browse ready-built computers available from our shop, with options for different needs and budgets.') }}</p>
                            <a class="btn btn-sm btn-primary rounded-pill" href="{{ route('shop.public', ['type' => 'ready_built_pc']) }}">
                                {{ __('Browse ready-built PCs') }} <span class="bi bi-arrow-right ms-1" aria-hidden="true"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <p class="small text-body-secondary text-center mt-3 mb-0">
                Illustrative stock photos from <a href="https://unsplash.com/license" target="_blank" rel="noopener noreferrer">Unsplash</a>; they do not depict actual OMG Computer repairs, PCs, customers, or portfolio projects.
            </p> --}}
            <div class="row justify-content-center mt-4" id="help" style="scroll-margin-top: 100px;">
                <div class="col-lg-10 text-center">
                    <p class="text-700 mb-0"><span class="fw-semibold text-1000">{{ __('Not sure what you need?') }}</span> <a href="#contact">{{ __('Tell us what’s going on') }}</a> {{ __('and we’ll help you choose a next step.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-how-it-works py-7" id="how-it-works">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <span class="text-primary fw-bold text-uppercase fs-10">{{ __('Straightforward support') }}</span>
                    <h2 class="fw-bold text-1000 mt-2">{{ __('A clearer path to a working computer') }}</h2>
                    <p class="text-700">{{ __('Book or contact us to arrange drop-off. We’ll diagnose the issue, share a written quote, and wait for your approval before carrying out the repair.') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary rounded-pill px-4" href="{{ route('bookings.create') }}">
                            <span class="bi bi-calendar-check me-2" aria-hidden="true"></span>{{ __('Book a service') }}
                        </a>
                        <a class="track-repair-button btn rounded-pill px-4" href="{{ route('repairs.track.form') }}">
                            <span class="bi bi-search me-2" aria-hidden="true"></span>{{ __('Track your repair') }}
                        </a>
                    </div>
                    <p class="small text-700 mt-3 mb-0">{{ __('Have your service number ready to check repair progress.') }}</p>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex mb-4">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">1</div>
                        <div class="ms-3">
                            <h3 class="h5 text-1000 mb-1">{{ __('Book and drop off') }}</h3>
                            <p class="text-700 mb-0">{{ __('Book an appointment or contact us to arrange a drop-off. Tell us the device model and what is happening.') }}</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">2</div>
                        <div class="ms-3">
                            <h3 class="h5 text-1000 mb-1">{{ __('Review the diagnosis and quote') }}</h3>
                            <p class="text-700 mb-0">{{ __('We’ll confirm the diagnostic fee before assessment, explain the findings and price, and get your approval before repairs or extra work.') }}</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">3</div>
                        <div class="ms-3">
                            <h3 class="h5 text-1000 mb-1">{{ __('Collect your device') }}</h3>
                            <p class="text-700 mb-0">{{ __('We’ll update you when the agreed work is complete and your device is ready for collection. Use your service number to check progress.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mt-5" id="about" style="scroll-margin-top: 100px;">
                <div class="col-lg-10 border-top pt-4 text-center">
                    <span class="text-primary fw-bold text-uppercase fs-10">{{ __('About OMG Computer') }}</span>
                    <p class="text-700 mt-2 mb-0">{{ __('Friendly local support for home and business users, with honest recommendations and practical solutions—without the jargon or pressure.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="website-pricing" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row align-items-center g-4 mb-5">
                <div class="col-lg-5">
                    <img
                        class="img-fluid rounded-4"
                        src="{{ asset('assets/img/omg/website-design-illustration.svg') }}"
                        alt="{{ __('Responsive business website shown on desktop and mobile screens') }}"
                        width="800"
                        height="520"
                        loading="lazy"
                    >
                </div>
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="text-primary fw-bold text-uppercase fs-10">{{ __('Website design & development') }}</span>
                    <h2 class="fw-bold text-1000 mt-2">{{ __('Website packages tailored to your needs') }}</h2>
                    <p class="text-700 mb-0">{{ __('Tell us what you need and we’ll provide a quote based on your project scope before work begins.') }}</p>
                    <a class="btn btn-success rounded-pill px-4 mt-3" href="{{ $websiteQuoteUrl }}" target="_blank" rel="noopener noreferrer">
                        <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>{{ __('Discuss a website project') }}
                    </a>
                    <a class="btn btn-primary rounded-pill px-4 mt-3 ms-sm-2" href="{{ route('website-previews.index') }}">
                        <span class="bi bi-images me-2" aria-hidden="true"></span>{{ __('Browse website previews') }}
                    </a>
                </div>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-file-text text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h3 class="h4 text-1000">{{ __('Starter landing page') }}</h3>
                            <p class="text-700">{{ __('A polished single-page website to introduce your business and help customers get in touch.') }}</p>
                            <ul class="text-700 ps-3 mb-0">
                                <li class="mb-2">{{ __('Responsive layout for mobile and desktop') }}</li>
                                <li class="mb-2">{{ __('Core business information and contact call-to-action') }}</li>
                                <li>{{ __('Basic on-page SEO setup') }}</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-body border-0 px-4 pb-4">
                            <a class="btn btn-primary rounded-pill px-4" href="#contact">{{ __('Request a quote') }}</a>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border border-primary shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="badge text-bg-primary mb-3">{{ __('Popular starting point') }}</span>
                            <h3 class="h4 text-1000">{{ __('Business website') }}</h3>
                            <p class="text-700">{{ __('A multi-page website for businesses that need room to explain their services and build trust.') }}</p>
                            <ul class="text-700 ps-3 mb-0">
                                <li class="mb-2">{{ __('Up to five pages with a consistent custom design') }}</li>
                                <li class="mb-2">{{ __('Contact form and clear navigation') }}</li>
                                <li>{{ __('Responsive layout and basic on-page SEO') }}</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-body border-0 px-4 pb-4">
                            <a class="btn btn-primary rounded-pill px-4" href="#contact">{{ __('Request a quote') }}</a>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-code-slash text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h3 class="h4 text-1000">{{ __('Custom website') }}</h3>
                            <p class="text-700">{{ __('For larger sites, online stores, integrations, or features tailored to your workflow.') }}</p>
                            <ul class="text-700 ps-3 mb-0">
                                <li class="mb-2">{{ __('Scope and features planned around your requirements') }}</li>
                                <li class="mb-2">{{ __('Custom functionality or third-party integrations') }}</li>
                                <li>{{ __('Final price provided after a project discussion') }}</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-body border-0 px-4 pb-4">
                            <a class="btn btn-primary rounded-pill px-4" href="#contact">{{ __('Request a quote') }}</a>
                        </div>
                    </article>
                </div>
            </div>
            <p class="small text-body-secondary text-center mt-4 mb-0">
                {{ __('Every project is quoted individually. Domain registration, hosting, paid themes or plugins, and other third-party charges will be discussed as part of your quote.') }}
            </p>
        </div>
    </section>

    <section class="py-7 bg-body-tertiary" id="faq" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-bold text-uppercase fs-10">{{ __('Q & A') }}</span>
                    <h2 class="display-5 fw-bold text-1000 mt-2">{{ __('Common computer questions') }}</h2>
                    <p class="lead text-700 mb-0">{{ __('Quick answers to questions we often hear.') }}</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="accordion" id="landingFaqAccordion">
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqOneHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqOne" aria-expanded="false" aria-controls="landingFaqOne">
                                    {{ __('Do you fix both laptops and desktops?') }}
                                </button>
                            </h3>
                            <div id="landingFaqOne" class="accordion-collapse collapse" aria-labelledby="landingFaqOneHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">{{ __('Yes. We support desktop and laptop computers, including troubleshooting, upgrades, and repair recommendations.') }}</div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqTwoHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqTwo" aria-expanded="false" aria-controls="landingFaqTwo">
                                    {{ __('How do I know if my computer needs repair or replacement?') }}
                                </button>
                            </h3>
                            <div id="landingFaqTwo" class="accordion-collapse collapse" aria-labelledby="landingFaqTwoHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">{{ __('It depends on the issue, the age of the device, and the cost of repair. We can assess the problem and explain your options.') }}</div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqThreeHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqThree" aria-expanded="false" aria-controls="landingFaqThree">
                                    {{ __('Can you help with a slow computer?') }}
                                </button>
                            </h3>
                            <div id="landingFaqThree" class="accordion-collapse collapse" aria-labelledby="landingFaqThreeHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">{{ __('Yes. We can check for software, storage, or hardware causes and recommend practical ways to improve performance.') }}</div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqFourHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqFour" aria-expanded="false" aria-controls="landingFaqFour">
                                    {{ __('Do you support business devices?') }}
                                </button>
                            </h3>
                            <div id="landingFaqFour" class="accordion-collapse collapse" aria-labelledby="landingFaqFourHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">{{ __('Yes. We help home and business users with troubleshooting, setup, and recommendations for device reliability and performance.') }}</div>
                            </div>
                        </div>
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                        <h3 class="accordion-header" id="landingFaqFiveHeading">
                            <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqFive" aria-expanded="false" aria-controls="landingFaqFive">
                                {{ __('How much will diagnosis and repair cost?') }}
                            </button>
                        </h3>
                        <div id="landingFaqFive" class="accordion-collapse collapse" aria-labelledby="landingFaqFiveHeading" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body text-700">{{ __('We’ll confirm the diagnostic fee with you before booking or assessment. Any “from” price is a starting estimate; the final written quote depends on the diagnosis. Parts and software are quoted separately where applicable, and we’ll get your approval before proceeding.') }}</div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                        <h3 class="accordion-header" id="landingFaqSixHeading">
                            <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqSix" aria-expanded="false" aria-controls="landingFaqSix">
                                {{ __('Is my repair covered by a warranty?') }}
                            </button>
                        </h3>
                        <div id="landingFaqSix" class="accordion-collapse collapse" aria-labelledby="landingFaqSixHeading" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body text-700">{{ __('Warranty coverage and duration vary by repair and parts. We’ll state the applicable coverage and period in your written quotation.') }}</div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 shadow-sm rounded">
                        <h3 class="accordion-header" id="landingFaqSevenHeading">
                            <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqSeven" aria-expanded="false" aria-controls="landingFaqSeven">
                                {{ __('How should I prepare my data before a repair?') }}
                            </button>
                        </h3>
                        <div id="landingFaqSeven" class="accordion-collapse collapse" aria-labelledby="landingFaqSevenHeading" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body text-700">{{ __('Please back up important data before handing over your device. We access files only when needed and with your permission, but we cannot guarantee against data loss. Read our') }} <a href="{{ route('privacy') }}">{{ __('Privacy Policy') }}</a> {{ __('for more information.') }}</div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="contact" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row align-items-center g-5">
                   <div class="col-lg-6">
                        <span class="contact-kicker fw-bold text-primary text-uppercase fs-10">{{ __('Ready for the next step?') }}</span>
                        <h2 class="h2 fw-bold text-1000 mt-2 mb-3">{{ __('Let’s get your computer sorted.') }}</h2>
                        <p class="contact-copy lead mb-4">{{ __('Tell us what is happening and what device needs attention. We’ll help you figure out the next step.') }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('bookings.create') }}" class="btn btn-primary rounded-pill px-4">{{ __('Book a service') }}</a>
                            <a href="#faq" class="btn btn-secondary rounded-pill px-4">{{ __('Read common questions') }}</a>
                        </div>
                    </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="bi bi-headset text-primary fs-2 mb-3"></span>
                            <h3 class="h3 fw-bold text-1000">{{ __('Need a hand?') }}</h3>
                            <p class="text-700 mb-0">{{ __('Get in touch with OMG Computer to discuss a repair, ask about support, or find out what to do next.') }}</p>
                            <a class="btn btn-primary rounded-pill px-4 mt-3" href="mailto:support@omgcomputer.my">
                                <span class="bi bi-envelope me-2" aria-hidden="true"></span>support@omgcomputer.my
                            </a>
                            <a class="btn btn-success rounded-pill px-4 mt-3" href="https://wa.me/60104224350?text=Hi%20OMG%20Computer%2C%20I%20would%20like%20to%20ask%20about%20your%20services." target="_blank" rel="noopener noreferrer">
                                <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>{{ __('Message us on WhatsApp') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
