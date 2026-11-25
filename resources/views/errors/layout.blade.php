@extends('layouts.auth')

@push('styles')
    <style>
        html[data-bs-theme="light"] .brand-logo-dark,
        html[data-bs-theme="dark"] .brand-logo-light {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="row flex-center min-vh-100 py-6 text-center">
        @if (request()->routeIs('error-pages.preview') && auth()->check())
            @unless (auth()->user()->hasRole('customer'))
                <aside class="col-12 col-md-4 col-lg-3" aria-label="Error page previews">
                    <div class="card mb-3">
                        <div class="card-body text-start">
                            <h2 class="h6 text-primary">Error page previews</h2>
                            <nav class="nav flex-column">
                                @foreach (['403' => __('Forbidden'), '404' => __('Not found'), '419' => __('Session expired'), '429' => __('Too many requests'), '500' => __('Server error'), '503' => __('Unavailable')] as $code => $label)
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
                <img class="brand-logo-light" src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer" width="120">
                <img class="brand-logo-dark" src="{{ asset('assets/img/omg/logoex2w.png') }}" alt="" width="120">
            </a>
            <div class="card">
                <div class="card-body p-4 p-sm-5">
                    <div class="fw-black lh-1 text-300 fs-error" aria-hidden="true">@yield('code')</div>
                    <h1 class="lead mt-4 text-800 font-sans-serif fw-semi-bold">{{ trim($__env->yieldContent('heading')) }}</h1>
                    <p class="mt-3">{{ trim($__env->yieldContent('message')) }}</p>
                    <hr>
                    <p>{{ __('If you need help') }}, <a href="{{ route('contact') }}">{{ __('contact us') }}</a>.</p>
                    <a class="btn btn-primary btn-sm mt-3" href="{{ url('/') }}">
                        <i class="bi bi-house me-2" aria-hidden="true"></i>{{ __('Take me home') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
