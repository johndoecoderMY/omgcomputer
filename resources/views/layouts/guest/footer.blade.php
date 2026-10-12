<footer class="bg-dark py-4">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md">
                <a class="d-inline-flex align-items-center text-decoration-none" href="{{ url('/') }}">
                    <img src="{{ asset('assets/img/omg/logo.png') }}" alt="" width="30" height="30" class="me-2">
                    <span class="text-white fw-bold">OMG Computer</span>
                </a>
                <p class="text-500 fs-10 mb-0 mt-2">Computer repair and support for home and work.</p>
            </div>
            <div class="col-md-auto text-md-end">
                <a class="link-300 me-3" href="{{ url('/') }}#services">Services</a>
                <a class="link-300" href="{{ url('/') }}#repair-tracking">Guest repair tracking</a>
                <p class="text-600 fs-10 mb-0 mt-2">&copy; {{ date('Y') }} OMG Computer</p>
            </div>
        </div>
    </div>
</footer>
