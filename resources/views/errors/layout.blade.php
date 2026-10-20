@extends('layouts.auth')

@section('content')
    <div class="row flex-center min-vh-100 py-6 text-center">
        @if (request()->routeIs('error-pages.preview') && auth()->check())
            @unless (auth()->user()->hasRole('customer'))
                <aside class="col-12 col-md-4 col-lg-3" aria-label="Error page previews">
                    <div class="card mb-3">
                        <div class="card-body text-start">
                            <h2 class="h6 text-primary">Error page previews</h2>
                            <nav class="nav flex-column">
                                @foreach (['403' => 'Forbidden', '404' => 'Not found', '419' => 'Session expired', '429' => 'Too many requests', '500' => 'Server error', '503' => 'Unavailable'] as $code => $label)
                                    <a class="nav-link px-0" href="{{ route('error-pages.preview', $code) }}">{{ $code }} · {{ $label }}</a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                </aside>
            @endunless
        @endif
        <div class="col-sm-10 col-md-8 col-lg-6 col-xxl-5">
            <a class="d-flex flex-center mb-4" href="{{ url('/') }}">
                <img src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer" width="120">
            </a>
            <div class="card">
                <div class="card-body p-4 p-sm-5">
                    <div class="fw-black lh-1 text-300 fs-error" aria-hidden="true">@yield('code')</div>
                    <h1 class="lead mt-4 text-800 font-sans-serif fw-semi-bold">{{ trim($__env->yieldContent('heading')) }}</h1>
                    <p class="mt-3">{{ trim($__env->yieldContent('message')) }}</p>
                    <hr>
                    <p>If you need help, <a href="{{ route('contact') }}">contact us</a>.</p>
                    <a class="btn btn-primary btn-sm mt-3" href="{{ url('/') }}">
                        <i class="fas fa-home me-2" aria-hidden="true"></i>Take me home
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
