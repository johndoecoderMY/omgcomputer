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
    <style>
        .landing-hero { min-height: 720px; padding-top: 118px; background: linear-gradient(135deg, var(--falcon-gray-100, #fff) 0%, var(--falcon-body-bg, #edf2f9) 100%); overflow: hidden; }
        .landing-hero .hero-title { max-width: 760px; color: var(--falcon-gray-1000, #172033); font-size: clamp(3rem, 6vw, 5.6rem); line-height: .96; letter-spacing: -.055em; }
        .landing-hero .hero-title span { color: var(--falcon-primary); }
        .landing-hero .hero-kicker { color: var(--falcon-primary); background: rgba(var(--falcon-primary-rgb), .08); border: 1px solid rgba(var(--falcon-primary-rgb), .18); }
        .landing-hero .hero-copy { max-width: 590px; }
        .landing-hero .hero-primary-button { color: #fff; background: var(--falcon-primary); border-color: var(--falcon-primary); box-shadow: 0 12px 30px rgba(var(--falcon-primary-rgb), .22); }
        .landing-hero .hero-primary-button:hover { filter: brightness(.92); }
        .landing-hero .hero-visual { position: relative; }
        .landing-hero .hero-image { border-radius: 28px; ; box-shadow: 0 28px 70px rgba(23,32,51,.16); }
        .landing-hero .hero-float { position: absolute; z-index: 2; display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,.96); border: 1px solid rgba(23,32,51,.08); border-radius: 16px; box-shadow: 0 16px 40px rgba(23,32,51,.14); }
        .landing-hero .hero-float-one { left: -24px; top: 18%; }
        .landing-hero .hero-float-two { right: -12px; bottom: 12%; }
        .landing-hero .hero-float-icon { width: 52px; height: 52px; display: grid; place-items: center; flex-shrink: 0; border-radius: 15px; color: var(--falcon-primary); background: rgba(var(--falcon-primary-rgb), .08); }
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

    <section class="landing-hero d-flex align-items-center" id="home">
        <div class="container">
            <div class="row align-items-center g-5 py-5">
                <div class="col-lg-6">
                    <span class="hero-kicker badge rounded-pill mb-4 px-3 py-2">
                        <span class="bi bi-tools me-2"></span>Computer repair, website design &amp; technology support
                    </span>
                    <h1 class="hero-title fw-bold mb-4">Computer trouble?<br><span>We’ll fix it.</span></h1>
                    <p class="hero-copy lead text-700 mb-4">Reliable repairs, upgrades and custom websites — delivered with clear advice and friendly local support.</p>
                    <div class="hero-actions row g-3">
                        <div class="col-12 col-lg-6">
                            <a class="hero-primary-button btn btn-lg rounded-pill px-3 w-100" href="{{ route('bookings.create') }}">
                                <span class="bi bi-calendar-check me-2" aria-hidden="true"></span>Book a service
                            </a>
                        </div>
                        <div class="col-12 col-lg-6">
                            <a class="btn btn-success btn-lg rounded-pill px-3 w-100" href="{{ $whatsappContactUrl }}" target="_blank" rel="noopener noreferrer">
                                <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>WhatsApp us
                            </a>
                        </div>
                        <div class="col-12 col-lg-6">
                            <a class="btn btn-outline-primary btn-lg rounded-pill px-3 w-100" href="{{ route('shop.public') }}">
                                <span class="bi bi-bag me-2" aria-hidden="true"></span>Shop
                            </a>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-5 text-700 small">
                        <span><span class="bi bi-check-circle-fill text-success me-2"></span>Clear diagnosis</span>
                        <span><span class="bi bi-check-circle-fill text-success me-2"></span>Practical solutions</span>
                        <span><span class="bi bi-check-circle-fill text-success me-2"></span>Friendly support</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual">
                        <img class="hero-image img-fluid w-100" src="{{ asset('assets/img/omg/hero-technician.jpg') }}" alt="Person working with a laptop at an electronics workbench" width="1400" height="800" fetchpriority="high" decoding="async">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7 bg-body-tertiary" id="services">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-bold text-uppercase fs-10">How we can help</span>
                    <h2 class="fw-bold text-1000 mt-2">Technology services for home and work</h2>
                    <p class="text-700 mb-0">From computer support to custom websites, get practical technology help for home and work.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/repair-board-work.jpg') }}" alt="Close-up of a technician inspecting an electronic circuit board" width="900" height="600" loading="lazy" decoding="async" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        <div class="card-body p-4">
                            <span class="bi bi-activity text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">PC diagnosis &amp; repair</h5>
                            <p class="text-700 mb-0">Troubleshoot crashes, slowdowns, startup issues, and hardware faults. Get a clear diagnosis and repair options.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/repair-pc-components.jpg') }}" alt="Close-up view of PC cooling and internal components" width="900" height="600" loading="lazy" decoding="async" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        <div class="card-body p-4">
                            <span class="bi bi-memory text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Upgrades &amp; software support</h5>
                            <p class="text-700 mb-0">Improve performance, resolve software issues, and get practical help with system and security updates.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/website-design-work.jpg') }}" alt="Laptop displaying website code at a desk" width="900" height="600" loading="lazy" decoding="async" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        <div class="card-body p-4">
                            <span class="bi bi-globe text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Website design &amp; development</h5>
                            <p class="text-700">Get a custom website designed and built for your business or organization.</p>
                            <a class="btn btn-sm btn-outline-primary rounded-pill" href="#website-pricing">
                                Explore website packages <span class="bi bi-arrow-down ms-1" aria-hidden="true"></span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <img class="img-fluid rounded-3" src="{{ asset('assets/img/omg/ready-built-pc.jpg') }}" alt="Example of a ready-built desktop PC with illuminated internal components" width="900" height="600" loading="lazy" decoding="async" style="aspect-ratio: 3 / 2; object-fit: cover;">
                        <div class="card-body p-4">
                            <span class="bi bi-pc-display-horizontal text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h5 class="text-1000">Ready-built custom PCs</h5>
                            <p class="text-700">Browse ready-built computers available from our shop, with options for different needs and budgets.</p>
                            <a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route('shop.public', ['type' => 'ready_built_pc']) }}">
                                Browse ready-built PCs <span class="bi bi-arrow-right ms-1" aria-hidden="true"></span>
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
                    <p class="text-700 mb-0"><span class="fw-semibold text-1000">Not sure what you need?</span> <a href="#contact">Tell us what’s going on</a> and we’ll help you choose a next step.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-how-it-works py-7" id="how-it-works">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <span class="text-primary fw-bold text-uppercase fs-10">Straightforward support</span>
                    <h2 class="fw-bold text-1000 mt-2">A clearer path to a working computer</h2>
                    <p class="text-700">Tell us what’s happening, get a diagnosis, and understand your options before moving ahead with a repair.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary rounded-pill px-4" href="{{ route('bookings.create') }}">
                            <span class="bi bi-calendar-check me-2" aria-hidden="true"></span>Book a service
                        </a>
                        <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('repairs.track.form') }}">
                            <span class="bi bi-search me-2" aria-hidden="true"></span>Track your repair
                        </a>
                    </div>
                    <p class="small text-700 mt-3 mb-0">Have your service number ready to check repair progress.</p>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex mb-4">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">1</div>
                        <div class="ms-3">
                            <h5 class="text-1000 mb-1">Tell us what’s going on</h5>
                            <p class="text-700 mb-0">Describe the issue and the device that needs attention.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">2</div>
                        <div class="ms-3">
                            <h5 class="text-1000 mb-1">Get a diagnosis and options</h5>
                            <p class="text-700 mb-0">We’ll explain what we find and discuss the next steps with you.</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">3</div>
                        <div class="ms-3">
                            <h5 class="text-1000 mb-1">Get back to work</h5>
                            <p class="text-700 mb-0">Follow the repair progress with the service number provided for your device.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mt-5" id="about" style="scroll-margin-top: 100px;">
                <div class="col-lg-10 border-top pt-4 text-center">
                    <span class="text-primary fw-bold text-uppercase fs-10">About OMG Computer</span>
                    <p class="text-700 mt-2 mb-0">Friendly local support for home and business users, with honest recommendations and practical solutions—without the jargon or pressure.</p>
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
                        alt="Responsive business website shown on desktop and mobile screens"
                        width="800"
                        height="520"
                        loading="lazy"
                    >
                </div>
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="text-primary fw-bold text-uppercase fs-10">Website design &amp; development</span>
                    <h2 class="fw-bold text-1000 mt-2">Website packages tailored to your needs</h2>
                    <p class="text-700 mb-0">Tell us what you need and we’ll provide a quote based on your project scope before work begins.</p>
                    <a class="btn btn-success rounded-pill px-4 mt-3" href="{{ $websiteQuoteUrl }}" target="_blank" rel="noopener noreferrer">
                        <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Discuss a website project
                    </a>
                    <a class="btn btn-outline-primary rounded-pill px-4 mt-3 ms-sm-2" href="{{ route('website-previews.index') }}">
                        <span class="bi bi-images me-2" aria-hidden="true"></span>Browse website previews
                    </a>
                </div>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-file-text text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h3 class="h4 text-1000">Starter landing page</h3>
                            <p class="text-700">A polished single-page website to introduce your business and help customers get in touch.</p>
                            <ul class="text-700 ps-3 mb-0">
                                <li class="mb-2">Responsive layout for mobile and desktop</li>
                                <li class="mb-2">Core business information and contact call-to-action</li>
                                <li>Basic on-page SEO setup</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-body border-0 px-4 pb-4">
                            <a class="btn btn-outline-primary rounded-pill px-4" href="#contact">Request a quote</a>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border border-primary shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="badge text-bg-primary mb-3">Popular starting point</span>
                            <h3 class="h4 text-1000">Business website</h3>
                            <p class="text-700">A multi-page website for businesses that need room to explain their services and build trust.</p>
                            <ul class="text-700 ps-3 mb-0">
                                <li class="mb-2">Up to five pages with a consistent custom design</li>
                                <li class="mb-2">Contact form and clear navigation</li>
                                <li>Responsive layout and basic on-page SEO</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-body border-0 px-4 pb-4">
                            <a class="btn btn-primary rounded-pill px-4" href="#contact">Request a quote</a>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-code-slash text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h3 class="h4 text-1000">Custom website</h3>
                            <p class="text-700">For larger sites, online stores, integrations, or features tailored to your workflow.</p>
                            <ul class="text-700 ps-3 mb-0">
                                <li class="mb-2">Scope and features planned around your requirements</li>
                                <li class="mb-2">Custom functionality or third-party integrations</li>
                                <li>Final price provided after a project discussion</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-body border-0 px-4 pb-4">
                            <a class="btn btn-outline-primary rounded-pill px-4" href="#contact">Request a quote</a>
                        </div>
                    </article>
                </div>
            </div>
            <p class="small text-body-secondary text-center mt-4 mb-0">
                Every project is quoted individually. Domain registration, hosting, paid themes or plugins, and other third-party charges will be discussed as part of your quote.
            </p>
        </div>
    </section>

    <section class="py-7 bg-body-tertiary" id="faq" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-bold text-uppercase fs-10">Q &amp; A</span>
                    <h2 class="display-5 fw-bold text-1000 mt-2">Common computer questions</h2>
                    <p class="lead text-700 mb-0">Quick answers to questions we often hear.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="accordion" id="landingFaqAccordion">
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqOneHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqOne" aria-expanded="false" aria-controls="landingFaqOne">
                                    Do you fix both laptops and desktops?
                                </button>
                            </h3>
                            <div id="landingFaqOne" class="accordion-collapse collapse" aria-labelledby="landingFaqOneHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">Yes. We support desktop and laptop computers, including troubleshooting, upgrades, and repair recommendations.</div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqTwoHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqTwo" aria-expanded="false" aria-controls="landingFaqTwo">
                                    How do I know if my computer needs repair or replacement?
                                </button>
                            </h3>
                            <div id="landingFaqTwo" class="accordion-collapse collapse" aria-labelledby="landingFaqTwoHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">It depends on the issue, the age of the device, and the cost of repair. We can assess the problem and explain your options.</div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded">
                            <h3 class="accordion-header" id="landingFaqThreeHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqThree" aria-expanded="false" aria-controls="landingFaqThree">
                                    Can you help with a slow computer?
                                </button>
                            </h3>
                            <div id="landingFaqThree" class="accordion-collapse collapse" aria-labelledby="landingFaqThreeHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">Yes. We can check for software, storage, or hardware causes and recommend practical ways to improve performance.</div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm rounded">
                            <h3 class="accordion-header" id="landingFaqFourHeading">
                                <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#landingFaqFour" aria-expanded="false" aria-controls="landingFaqFour">
                                    Do you support business devices?
                                </button>
                            </h3>
                            <div id="landingFaqFour" class="accordion-collapse collapse" aria-labelledby="landingFaqFourHeading" data-bs-parent="#landingFaqAccordion">
                                <div class="accordion-body text-700">Yes. We help home and business users with troubleshooting, setup, and recommendations for device reliability and performance.</div>
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
                        <span class="contact-kicker fw-bold text-uppercase fs-10">Ready for the next step?</span>
                        <h2 class="h2 fw-bold text-white mt-2 mb-3">Let’s get your computer sorted.</h2>
                        <p class="contact-copy lead mb-4">Tell us what is happening and what device needs attention. We’ll help you figure out the next step.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('bookings.create') }}" class="btn btn-primary rounded-pill px-4">Book a service</a>
                            <a href="#faq" class="btn btn-outline-primary rounded-pill px-4">Read common questions</a>
                        </div>
                    </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="bi bi-headset text-primary fs-2 mb-3"></span>
                            <h3 class="h3 fw-bold text-1000">Need a hand?</h3>
                            <p class="text-700 mb-0">Get in touch with OMG Computer to discuss a repair, ask about support, or find out what to do next.</p>
                                                            <a class="btn btn-outline-primary rounded-pill px-4 mt-3" href="mailto:support@omgcomputer.my">
                                    <span class="bi bi-envelope me-2" aria-hidden="true"></span>support@omgcomputer.my
                                </a>
                                                        <a class="btn btn-success rounded-pill px-4 mt-3" href="https://wa.me/60104224350?text=Hi%20OMG%20Computer%2C%20I%20would%20like%20to%20ask%20about%20your%20services." target="_blank" rel="noopener noreferrer"><span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Message us on WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
