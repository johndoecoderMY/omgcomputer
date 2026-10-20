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
                <div class="d-flex flex-wrap justify-content-md-end gap-3">
                    <a class="link-300" href="{{ url('/') }}#services">Services</a>
                    <a class="link-300" href="{{ url('/') }}#about">About</a>
                    <a class="link-300" href="{{ url('/') }}#help">Help</a>
                    <a class="link-300" href="{{ url('/') }}#faq">Q &amp; A</a>
                    <a class="link-300" href="{{ url('/') }}#contact">Contact</a>
                    <a class="link-300" href="{{ route('privacy') }}">Privacy</a>
                    <a class="link-300" href="{{ route('toc') }}">TOC</a>
                </div>
                <p class="text-600 fs-10 mb-0 mt-2">&copy; {{ date('Y') }} OMG Computer</p>
            </div>
        </div>
    </div>
</footer>
