@extends('layouts.guest')

@section('title', 'Computer Repair & Support')

@section('content')
    @php
        $whatsappContactUrl = 'https://wa.me/60104224350?text=' . rawurlencode('Hi OMG Computer, I would like to ask about your services.');
        $configuredContactEmail = config('mail.from.address');
        $contactEmail = is_string($configuredContactEmail)
            && filter_var($configuredContactEmail, FILTER_VALIDATE_EMAIL)
            && $configuredContactEmail !== 'hello@example.com'
                ? $configuredContactEmail
                : null;
    @endphp
    <section class="py-8 py-lg-10 bg-body-tertiary overflow-hidden" id="home" style="min-height: 78vh;">
        <div class="container">
            <div class="row align-items-center g-5 py-lg-4">
                <div class="col-lg-6">
                    <span class="badge rounded-pill badge-subtle-primary mb-3 px-3 py-2">
                        <span class="bi bi-tools me-2"></span>Local computer repair &amp; support
                    </span>
                    <h1 class="display-2 fw-bold text-1000 mb-3">Computer trouble?<br><span class="text-primary">We can help.</span></h1>
                    <p class="lead text-700 mb-4">Friendly computer repairs and support, plus custom websites for businesses and organizations.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="#services">Explore our services</a>
                        <a class="btn btn-success btn-lg rounded-pill px-4" href="{{ route('bookings.create') }}"><span class="bi bi-calendar-check me-2"></span>Book an appointment</a>
                        <a class="btn btn-outline-primary btn-lg rounded-pill px-4" href="{{ $whatsappContactUrl }}" target="_blank" rel="noopener noreferrer"><span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Contact us</a>
                        <a class="btn btn-outline-primary btn-lg rounded-pill px-4" href="{{ route('repairs.track.form') }}">
                            <span class="bi bi-search me-2"></span>Track a repair
                        </a>
                        <a class="btn btn-outline-secondary btn-lg rounded-pill px-4" href="{{ route('shop.public') }}">
                            <span class="bi bi-shop me-2" aria-hidden="true"></span>Browse the shop
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-5 text-700">
                        <span><span class="bi bi-check-circle text-primary me-2"></span>Clear explanations</span>
                        <span><span class="bi bi-check-circle text-primary me-2"></span>Practical solutions</span>
                    </div>
                    @if ($companyProfile->facebook_url)
                        <a class="btn btn-outline-primary rounded-pill px-4 mt-4" href="{{ $companyProfile->facebook_url }}" target="_blank" rel="noopener noreferrer">
                            <span class="bi bi-facebook me-2" aria-hidden="true"></span>Follow us on Facebook
                        </a>
                    @endif
                </div>
                <div class="col-lg-6">
                    <img
                        class="img-fluid w-100 rounded-4 shadow-lg"
                        src="{{ asset('assets/img/omg/computer-repair-placeholder.svg') }}"
                        alt="Illustration of a desktop computer, laptop, and repair tools"
                        width="960"
                        height="680"
                        fetchpriority="high"
                    >
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
                <div class="col-md-6 col-xl">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-activity text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Diagnostics &amp; troubleshooting</h5>
                            <p class="text-700 mb-0">Find the cause of crashes, slowdowns, startup issues, and other PC problems.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-tools text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">PC &amp; laptop repairs</h5>
                            <p class="text-700 mb-0">Get help with hardware faults, damaged components, and repair recommendations.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-memory text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Upgrades &amp; tune-ups</h5>
                            <p class="text-700 mb-0">Improve performance with storage, memory, component, and system upgrades.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-shield-check text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Software &amp; security support</h5>
                            <p class="text-700 mb-0">Resolve software issues and get practical help protecting your devices.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-globe text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Website design &amp; development</h5>
                            <p class="text-700 mb-0">Get a custom website designed and built for your business or organization.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="website-pricing" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-bold text-uppercase fs-10">Website design &amp; development</span>
                    <h2 class="fw-bold text-1000 mt-2">Website packages tailored to your needs</h2>
                    <p class="text-700 mb-0">Tell us what you need and we’ll provide a quote based on your project scope before work begins.</p>
                </div>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="bi bi-file-text text-primary fs-4 mb-3" aria-hidden="true"></span>
                            <h3 class="h4 text-1000">Starter landing page</h3>
                            <p class="text-700">A polished single-page website to introduce your business and help customers get in touch.</p>
                            <p class="h3 text-primary mb-3">Request a quote</p>
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
                            <p class="h3 text-primary mb-3">Request a quote</p>
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
                            <p class="h3 text-primary mb-3">Request a quote</p>
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

    <section class="py-7" id="how-it-works">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <span class="text-primary fw-bold text-uppercase fs-10">Straightforward support</span>
                    <h2 class="fw-bold text-1000 mt-2">A clearer path to a working computer</h2>
                    <p class="text-700">Tell us what’s happening, get a diagnosis, and understand your options before moving ahead with a repair.</p>
                    <a class="btn btn-primary rounded-pill px-4" href="#booking">Get started</a>
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
        </div>
    </section>

    <section class="py-7 bg-body-tertiary" id="about" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <img
                        class="img-fluid w-100 rounded-4 shadow"
                        src="{{ asset('assets/img/omg/computer-repair-placeholder.svg') }}"
                        alt="Illustration of computer equipment and repair tools"
                        width="960"
                        height="680"
                        loading="lazy"
                    >
                </div>
                <div class="col-lg-7">
                    <span class="text-primary fw-bold text-uppercase fs-10">About OMG Computer</span>
                    <h2 class="display-5 fw-bold text-1000 mt-2 mb-3">Friendly local tech support with a personal touch.</h2>
                    <p class="lead text-700">We help people and businesses get their computers working again without the confusion, jargon, or pressure.</p>
                    <p class="text-700">Our goal is to make technology easier to understand and use. Whether you need a quick fix, a full diagnosis, or a performance upgrade, we focus on practical solutions and clear advice.</p>
                    <div class="row g-3 mt-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="bi bi-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Honest explanations and recommendations</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="bi bi-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Support for home and business users</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="bi bi-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Practical solutions to get you back to work</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="bi bi-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Respectful service from start to finish</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="help" style="scroll-margin-top: 100px;">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-primary fw-bold text-uppercase fs-10">Help center</span>
                    <h2 class="display-5 fw-bold text-1000 mt-2">We’re here to help you get unstuck.</h2>
                    <p class="lead text-700 mb-0">A few common problems we can help diagnose and resolve.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="bi bi-lightning text-primary fs-3 mb-3"></span>
                            <h3 class="h4 text-1000">Slow or unresponsive computer</h3>
                            <p class="text-700 mb-0">Startup programs, low storage, software problems, or aging hardware can affect performance. We can help find the cause and recommend a fix.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="bi bi-laptop text-primary fs-3 mb-3"></span>
                            <h3 class="h4 text-1000">Hardware problems</h3>
                            <p class="text-700 mb-0">If your screen, keyboard, battery, or fans are acting up, we can assess whether repair or replacement makes sense.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="bi bi-shield-shaded text-primary fs-3 mb-3"></span>
                            <h3 class="h4 text-1000">Security and software issues</h3>
                            <p class="text-700 mb-0">Get help with software conflicts, security concerns, update issues, and unwanted programs.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5">
                            <h3 class="h4 fw-bold text-1000 mb-3">Before you ask for support</h3>
                            <p class="text-700 mb-0">Have your device’s make and model handy, note when the issue started, and include any error messages you see. This helps us understand the problem more quickly.</p>
                        </div>
                    </div>
                </div>
            </div>
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
                    <span class="text-primary fw-bold text-uppercase fs-10">Contact</span>
                    <h2 class="display-5 fw-bold text-1000 mt-2 mb-3">Let’s get your computer sorted.</h2>
                    <p class="lead text-700">Tell us what is happening and what device needs attention. We’ll help you figure out the next step.</p>
                    <a href="#faq" class="btn btn-outline-primary rounded-pill px-4">Read common questions</a>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="bi bi-headset text-primary fs-2 mb-3"></span>
                            <h3 class="h3 fw-bold text-1000">Need a hand?</h3>
                            <p class="text-700 mb-0">Get in touch with OMG Computer to discuss a repair, ask about support, or find out what to do next.</p>
                            @if ($contactEmail)
                                <a class="btn btn-outline-primary rounded-pill px-4 mt-3" href="mailto:{{ $contactEmail }}">
                                    <span class="bi bi-envelope me-2" aria-hidden="true"></span>{{ $contactEmail }}
                                </a>
                            @endif
                            <a class="btn btn-success rounded-pill px-4 mt-3" href="{{ $whatsappContactUrl }}" target="_blank" rel="noopener noreferrer"><span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Message us on WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7 bg-body-tertiary" id="booking">
        <div class="container">
            <div class="card border-0 shadow-sm overflow-hidden p-2">
                <div class="row g-0 align-items-center">
                    <div class="col-lg-8">
                        <div class="card-body p-4 p-lg-5">
                            <span class="text-primary fw-bold text-uppercase fs-10">Book a service</span>
                            <h2 class="fw-bold text-1000 mt-2">Ready to get your PC checked?</h2>
                            <p class="text-700 mb-0">Choose the service you need and request a convenient appointment online. Our team will contact you to confirm the time.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 bg-primary text-center p-4 p-lg-5">
                        <span class="bi bi-calendar-check bi-3x text-white mb-3"></span>
                        <p class="text-white mb-3">Request a booking online and our team will contact you to confirm.</p>
                        <a class="btn btn-light btn-lg rounded-pill px-4" href="{{ route('bookings.create') }}"><span class="bi bi-calendar-check me-2"></span>Book an appointment</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="repair-tracking">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-md-5 text-center">
                            <span class="bi bi-search text-primary bi-2x mb-3"></span>
                            <h2 class="fw-bold text-1000">Track your repair</h2>
                            <p class="text-700 mb-4">Check the latest progress using the service number provided by our shop.</p>
                            <a class="btn btn-primary rounded-pill px-4" href="{{ route('repairs.track.form') }}">Track a repair</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
