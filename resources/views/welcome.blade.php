@extends('layouts.guest')

@section('title', 'Computer Repair & Support')

@section('content')
    <section class="py-8 py-lg-10 overflow-hidden" id="home">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge rounded-pill badge-subtle-primary mb-3 px-3 py-2">
                        <span class="fas fa-tools me-2"></span>Local computer repair &amp; support
                    </span>
                    <h1 class="display-4 fw-bold text-1000 mb-3">Computer trouble?<br><span class="text-primary">We can help.</span></h1>
                    <p class="lead text-700 mb-4">Friendly, dependable help for slow PCs, broken hardware, upgrades, and everyday tech headaches.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="#services">Explore our services</a>
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
                    <div class="card border-0 shadow-lg overflow-hidden" style="background:linear-gradient(135deg,#f34303 0%,#ffb51b 100%);">
                        <div class="card-body p-4 p-md-5 text-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-uppercase fw-bold fs-10 ls-1 mb-2">OMG Computer</p>
                                    <h2 class="text-white fw-bold mb-2">Tech support,<br>without the stress.</h2>
                                    <p class="mb-0 opacity-75">From diagnosis to getting you back up and running.</p>
                                </div>
                                <span class="fas fa-laptop-medical fa-3x opacity-75 ms-3"></span>
                            </div>
                            <div class="bg-white rounded-3 text-900 p-3 mt-5">
                                <div class="d-flex align-items-center">
                                    <span class="fas fa-screwdriver-wrench text-primary fs-5 me-3"></span>
                                    <div>
                                        <h6 class="mb-1">Not sure what’s wrong?</h6>
                                        <p class="text-600 fs-10 mb-0">We’ll help you figure out the next step.</p>
                                    </div>
                                    <span class="fas fa-arrow-right text-primary ms-auto"></span>
                                </div>
                            </div>
                        </div>
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
                    <h2 class="fw-bold text-1000 mt-2">Computer services for home and work</h2>
                    <p class="text-700 mb-0">Get support for the problems that slow you down, with clear advice at every step.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="fas fa-stethoscope text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Diagnostics &amp; troubleshooting</h5>
                            <p class="text-700 mb-0">Find the cause of crashes, slowdowns, startup issues, and other PC problems.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="fas fa-screwdriver-wrench text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">PC &amp; laptop repairs</h5>
                            <p class="text-700 mb-0">Get help with hardware faults, damaged components, and repair recommendations.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="fas fa-memory text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Upgrades &amp; tune-ups</h5>
                            <p class="text-700 mb-0">Improve performance with storage, memory, component, and system upgrades.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="fas fa-shield-virus text-primary fs-4 mb-3"></span>
                            <h5 class="text-1000">Software &amp; security support</h5>
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

    <section class="py-7 bg-body-tertiary" id="booking">
        <div class="container">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="row g-0 align-items-center">
                    <div class="col-lg-8">
                        <div class="card-body p-4 p-lg-5">
                            <span class="text-primary fw-bold text-uppercase fs-10">Book a service</span>
                            <h2 class="fw-bold text-1000 mt-2">Ready to get your PC checked?</h2>
                            <p class="text-700 mb-lg-0">Online service booking is coming soon. In the meantime, contact the shop directly to arrange a repair or support visit.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 bg-primary text-center p-4 p-lg-5">
                        <span class="fas fa-calendar-check fa-3x text-white mb-3"></span>
                        <p class="text-white mb-0">We’re preparing online booking so you can request a service right here.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-7" id="repair-tracking">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card border-0 shadow">
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
