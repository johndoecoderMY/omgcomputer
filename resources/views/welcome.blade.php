@extends('layouts.guest')

@section('title', 'Computer Repair & Support')

@section('content')
    <section class="py-8 py-lg-10 bg-body-tertiary overflow-hidden" id="home" style="min-height: 78vh;">
        <div class="container">
            <div class="row align-items-center g-5 py-lg-4">
                <div class="col-lg-6">
                    <span class="badge rounded-pill badge-subtle-primary mb-3 px-3 py-2">
                        <span class="fas fa-tools me-2"></span>Local computer repair &support
                    </span>
                    <h1 class="display-2 fw-bold text-1000 mb-3">Computer trouble?<br><span class="text-primary">We can help.</span></h1>
                    <p class="lead text-700 mb-4">Friendly, dependable help for slow PCs, broken hardware, upgrades, and everyday tech headaches.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="#services">Explore our services</a>
                        <a class="btn btn-success btn-lg rounded-pill px-4" href="{{ route('bookings.create') }}"><span class="fas fa-calendar-check me-2"></span>Book an appointment</a>
                        <a class="btn btn-outline-primary btn-lg rounded-pill px-4" href="#repair-tracking">
                            <span class="fas fa-search me-2"></span>Track a repair
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-5 text-700">
                        <span><span class="fas fa-check-circle text-primary me-2"></span>Clear explanations</span>
                        <span><span class="fas fa-check-circle text-primary me-2"></span>Practical solutions</span>
                    </div>
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
                    <h2 class="fw-bold text-1000 mt-2">Computer services for home and work</h2>
                    <p class="text-700 mb-0">Get support for the problems that slow you down, with clear advice at every step.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="fas fa-stethoscope text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Diagnostics &troubleshooting</h5>
                            <p class="text-700 mb-0">Find the cause of crashes, slowdowns, startup issues, and other PC problems.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="fas fa-screwdriver-wrench text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">PC &laptop repairs</h5>
                            <p class="text-700 mb-0">Get help with hardware faults, damaged components, and repair recommendations.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="fas fa-memory text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Upgrades &tune-ups</h5>
                            <p class="text-700 mb-0">Improve performance with storage, memory, component, and system upgrades.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm p-2">
                        <div class="card-body p-4">
                            <span class="fas fa-shield-virus text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Software &security support</h5>
                            <p class="text-700 mb-0">Resolve software issues and get practical help protecting your devices.</p>
                        </div>
                    </div>
                </div>
            </div>
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
                                <span class="fas fa-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Honest explanations and recommendations</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="fas fa-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Support for home and business users</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="fas fa-check-circle text-primary mt-1 me-2"></span>
                                <span class="text-700">Practical solutions to get you back to work</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <span class="fas fa-check-circle text-primary mt-1 me-2"></span>
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
                            <span class="fas fa-bolt text-primary fs-3 mb-3"></span>
                            <h3 class="h4 text-1000">Slow or unresponsive computer</h3>
                            <p class="text-700 mb-0">Startup programs, low storage, software problems, or aging hardware can affect performance. We can help find the cause and recommend a fix.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="fas fa-laptop text-primary fs-3 mb-3"></span>
                            <h3 class="h4 text-1000">Hardware problems</h3>
                            <p class="text-700 mb-0">If your screen, keyboard, battery, or fans are acting up, we can assess whether repair or replacement makes sense.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 p-2">
                        <div class="card-body p-4 p-lg-5">
                            <span class="fas fa-shield-halved text-primary fs-3 mb-3"></span>
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
                    <span class="text-primary fw-bold text-uppercase fs-10">Q&amp;A</span>
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
                            <span class="fas fa-headset text-primary fs-2 mb-3"></span>
                            <h3 class="h3 fw-bold text-1000">Need a hand?</h3>
                            <p class="text-700 mb-0">Get in touch with OMG Computer to discuss a repair, ask about support, or find out what to do next. Please use the contact details provided by the shop.</p>
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
                        <span class="fas fa-calendar-check fa-3x text-white mb-3"></span>
                        <p class="text-white mb-3">Request a booking online and our team will contact you to confirm.</p>
                        <a class="btn btn-light btn-lg rounded-pill px-4" href="{{ route('bookings.create') }}"><span class="fas fa-calendar-check me-2"></span>Book an appointment</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="repair-tracking">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card border-0 shadow p-2">
                        <div class="card-body p-4 p-md-5 text-center">
                            <span class="fas fa-magnifying-glass text-primary fa-2x mb-3"></span>
                            <span class="d-block text-primary fw-bold text-uppercase fs-10">Guest repair tracking</span>
                            <h2 class="fw-bold text-1000 mt-2">Have a service number?</h2>
                            <p class="text-700">Guest tracking is coming soon. Keep the service number provided by the shop handy; you’ll be able to check your repair progress here.</p>
                            <form class="row g-2 justify-content-center mt-3" aria-label="Repair tracking lookup">
                                <div class="col-sm-8">
                                    <label class="visually-hidden" for="service-number">Service number</label>
                                    <input class="form-control" id="service-number" type="text" placeholder="Enter your service number" disabled>
                                </div>
                                <div class="col-sm-auto">
                                    <button class="btn btn-primary w-100" type="button" disabled>Track repair</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
