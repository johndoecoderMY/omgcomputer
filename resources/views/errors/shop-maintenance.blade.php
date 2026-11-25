@extends('layouts.guest')

@section('title', __('Shop coming soon'))
@section('meta_description', __('The OMG Computer online shop is being refreshed and will return soon.'))

@push('styles')
<style>
    .shop-coming-soon {
        position: relative;
        min-height: calc(100vh - 4rem);
        display: flex;
        align-items: center;
        overflow: hidden;
        padding: 7.5rem 0 5rem;
        background:
            radial-gradient(circle at 82% 18%, rgba(243, 67, 3, .12), transparent 23rem),
            linear-gradient(135deg, #f8fafc 0%, #fff 54%, #fff8f3 100%);
    }

    [data-bs-theme="dark"] .shop-coming-soon {
        background:
            radial-gradient(circle at 82% 18%, rgba(243, 67, 3, .18), transparent 23rem),
            linear-gradient(135deg, #111827 0%, #0d1523 58%, #25150f 100%);
    }

    .shop-coming-soon::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(20, 91, 115, .055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(20, 91, 115, .055) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: linear-gradient(to right, transparent, #000 20%, #000 80%, transparent);
    }

    .shop-coming-soon-card {
        position: relative;
        z-index: 1;
        border: 1px solid rgba(20, 91, 115, .12);
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 1.5rem 4rem rgba(20, 91, 115, .12);
        backdrop-filter: blur(12px);
    }

    [data-bs-theme="dark"] .shop-coming-soon-card {
        border-color: rgba(255, 255, 255, .08);
        background: rgba(13, 21, 35, .94);
        box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .28);
    }

    .shop-coming-soon-kicker {
        color: #b83400;
        letter-spacing: .16em;
    }

    .shop-coming-soon-art {
        min-height: 28rem;
        border-radius: 1.1rem;
        overflow: hidden;
        background: #101827;
    }

    .shop-coming-soon-art picture,
    .shop-coming-soon-art img {
        width: 100%;
        height: 100%;
        display: block;
    }

    .shop-coming-soon-art img {
        object-fit: cover;
        filter: saturate(.92) contrast(1.04);
    }

    .shop-coming-soon-art::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(135deg, rgba(16, 24, 39, .18), rgba(243, 67, 3, .12));
        pointer-events: none;
    }

    .shop-coming-soon-status {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        border: 1px solid rgba(243, 67, 3, .18);
        border-radius: 999px;
        padding: .55rem .85rem;
        background: rgba(243, 67, 3, .07);
        color: #b83400;
        font-size: .82rem;
        font-weight: 700;
    }

    .shop-coming-soon-status-dot {
        width: .55rem;
        height: .55rem;
        border-radius: 50%;
        background: #f34303;
        box-shadow: 0 0 0 .32rem rgba(243, 67, 3, .12);
    }

    @media (max-width: 991.98px) {
        .shop-coming-soon {
            min-height: auto;
            padding: 7rem 0 4rem;
        }

        .shop-coming-soon-art {
            min-height: 20rem;
        }
    }

    @media (max-width: 575.98px) {
        .shop-coming-soon {
            padding-top: 6.5rem;
        }

        .shop-coming-soon-art {
            min-height: 15rem;
        }
    }
</style>
@endpush

@section('content')
<section class="shop-coming-soon" aria-labelledby="shop-coming-soon-heading">
    <div class="container">
        <div class="shop-coming-soon-card p-3 p-md-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-6 d-flex">
                    <div class="p-3 p-md-4 d-flex flex-column justify-content-center">
                        <div class="shop-coming-soon-status mb-4">
                            <span class="shop-coming-soon-status-dot" aria-hidden="true"></span>
                            {{ __('SHOP UPDATE') }}
                        </div>
                        <p class="shop-coming-soon-kicker text-uppercase fw-bold small mb-3">{{ __('OMG Computer Store') }}</p>
                        <h1 class="display-5 fw-bold mb-3" id="shop-coming-soon-heading">{{ __('Our shop is coming soon') }}</h1>
                        <p class="lead text-body-secondary mb-4">
                            {{ __('We are refreshing the store experience to make parts, ready-built PCs, and online checkout easier to find.') }}
                        </p>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary rounded-pill px-4" href="{{ url('/') }}">
                                <span class="bi bi-house me-2" aria-hidden="true"></span>{{ __('Back to home') }}
                            </a>
                            <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('custom-pc-builder.public') }}">
                                <span class="bi bi-pc-display-horizontal me-2" aria-hidden="true"></span>{{ __('Explore custom PC') }}
                            </a>
                        </div>

                        <p class="small text-body-secondary mt-4 mb-0">
                            {{ __('Need help or looking for a specific component?') }}
                            <a class="fw-semibold" href="{{ route('contact') }}">{{ __('Contact OMG Computer') }}</a>.
                        </p>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="shop-coming-soon-art position-relative">
                        <picture>
                            <source type="image/webp" srcset="{{ asset('assets/img/omg/ready-built-pc-480.webp') }} 480w, {{ asset('assets/img/omg/ready-built-pc-720.webp') }} 720w, {{ asset('assets/img/omg/ready-built-pc-900.webp') }} 900w">
                            <img src="{{ asset('assets/img/omg/ready-built-pc.jpg') }}" alt="A high-performance ready-built PC">
                        </picture>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
