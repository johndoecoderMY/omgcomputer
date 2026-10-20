@props(['title', 'description' => null])

<div class="row min-vh-100 flex-center g-0 ">
    <div class="col-lg-8 col-xxl-5 py-3 position-relative">
        <img class="bg-auth-circle-shape" src="{{ asset('assets/img/icons/spot-illustrations/bg-shape.png') }}"
            alt="" width="250">
        <img class="bg-auth-circle-shape-2" src="{{ asset('assets/img/icons/spot-illustrations/shape-1.png') }}"
            alt="" >

        <div class="card overflow-hidden z-1">
            <div class="card-body p-0">
                <div class="row g-0 h-100">
                    <div class="col-md-5 text-center bg-card-gradient">
                        <div class="position-relative p-4 pt-md-5 pb-md-7" data-bs-theme="light">
                            <div class="bg-holder bg-auth-card-shape"
                                style="background-image: url('{{ asset('assets/img/icons/spot-illustrations/half-circle.png') }}');">
                            </div>
                            <div class="z-1 position-relative">
                                <a class="link-light mb-4 d-inline-block" href="{{ url('/') }}"
                                    aria-label="OMG Computer home">
                                    <img src="{{ asset('assets/img/omg/logoex2w.png') }}" alt="OMG Computer" width="145">
                                </a>
                                <p class="text-uppercase small fw-bold text-white opacity-75 mb-2">
                                    Computer care made simple
                                </p>
                                <h2 class="h4 text-white mb-3">Here for your next fix.</h2>
                                <p class="text-white opacity-75 mb-0">
                                    Keep your repairs, bookings, and project updates in one place.
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 mb-4 mt-md-4 mb-md-5 px-4" data-bs-theme="light">
                            @if (Route::is('login'))
                                <p class="text-white mb-0">
                                    New to OMG Computer?<br>
                                    <a class="btn btn-outline-light mt-2 px-4" href="{{ route('register') }}">
                                        Create an account
                                    </a>
                                </p>
                            @elseif (Route::is('register'))
                                <p class="text-white mb-0">
                                    Already have an account?<br>
                                    <a class="btn btn-outline-light mt-2 px-4" href="{{ route('login') }}">Sign in</a>
                                </p>
                            @else
                                <p class="text-white mb-0">
                                    <a class="text-decoration-underline link-light" href="{{ route('login') }}">
                                        Back to sign in
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-7 d-flex flex-center">
                        <div class="p-4 p-md-5 flex-grow-1">
                            <h1 class="h3 mb-2">{{ $title }}</h1>
                            @if ($description)
                                <p class="text-body-secondary mb-4">{{ $description }}</p>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
