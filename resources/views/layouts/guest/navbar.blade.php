<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('assets/img/omg/logoex2w.png') }}" alt=""   height="72" class="me-2">
            {{-- <span class="fw-bold">OMG Computer</span> --}}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guestNavbar" aria-controls="guestNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="guestNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#how-it-works">How it works</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#help">Help</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#faq">Q&amp;A</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#contact">Contact</a></li>
                <li class="nav-item ms-lg-3"><a class="nav-link" href="{{ route('login') }}">Sign in</a></li>
                <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm rounded-pill px-3" href="{{ url('/') }}#booking">Book a service</a></li>
            </ul>
        </div>
    </div>
</nav>
