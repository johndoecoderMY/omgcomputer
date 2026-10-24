<footer class="bg-dark pt-8 pb-4" >
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-4">
                <a class="d-inline-flex align-items-center text-decoration-none mb-3" href="{{ url('/') }}">
                    <img src="{{ asset('assets/img/omg/logoex2w.png') }}" alt="OMG Computer" height="56">
                </a>
                <h2 class="h6 text-uppercase text-white opacity-85 mb-3">Our mission</h2>
                <p class="text-600 mb-0">We help people and businesses solve computer problems with practical solutions, clear advice, and friendly local support. From repairs and upgrades to custom websites, our goal is to make technology easier to use.</p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-primary btn-sm rounded-pill px-3" href="{{ route('bookings.create') }}">Book a service</a>
                    <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="{{ route('repairs.track.form') }}">Track a repair</a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row gy-4">
                    <div class="col-6 col-md-4">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">Company</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#about">About us</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#contact">Contact</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#help">Help center</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#faq">Frequently asked questions</a></li>
                            <li><a class="link-600" href="{{ route('bookings.create') }}">Book an appointment</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md-4">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">Services</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">Computer repairs</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">Upgrades &amp; tune-ups</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">Software support</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">Website design &amp; development</a></li>
                            <li><a class="link-600" href="{{ route('shop.preview.products.grid') }}">Shop preview</a></li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-4">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">Quick links</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ route('repairs.track.form') }}">Track a repair</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('privacy') }}">Privacy policy</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('terms') }}">Terms of service</a></li>
                            @auth
                                <li><a class="link-600" href="{{ route('dashboard') }}">Go to workspace</a></li>
                            @else
                                <li><a class="link-600" href="{{ route('login') }}">Sign in</a></li>
                                <li><a class="link-600" href="{{ route('register') }}">Create an account</a></li>
                            @endauth
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
            <p class="text-600 fs-10 mb-0">&copy; {{ date('Y') }} OMG Computer. All rights reserved.</p>
         </div>
    </div>
</footer>
