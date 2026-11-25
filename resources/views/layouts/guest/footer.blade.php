<footer class="bg-dark pt-8 pb-4" >
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-4">
                <a class="d-inline-flex align-items-center text-decoration-none mb-3" href="{{ url('/') }}">
                    <picture>
                        <source type="image/webp" srcset="{{ asset('assets/img/omg/logoex2w-120.webp') }} 1x, {{ asset('assets/img/omg/logoex2w-240.webp') }} 2x">
                        <img src="{{ asset('assets/img/omg/logoex2w.png') }}" alt="OMG Computer" width="106" height="56">
                    </picture>
                </a>
                <h2 class="h6 text-uppercase text-white opacity-85 mb-3">{{ __('Our mission') }}</h2>
                <p class="text-600 mb-0">{{ __('We help people and businesses with PC repairs, upgrades, ready-built computers, and custom websites—using practical solutions, clear advice, and friendly local support.') }}</p>
                @if ($companyProfile->legal_name || $companyProfile->ssm_registration_number)
                    <div class="text-600 small mt-3">
                        @if ($companyProfile->legal_name)<div>{{ $companyProfile->legal_name }}</div>@endif
                        @if ($companyProfile->ssm_registration_number)<div>SSM registration no.: {{ $companyProfile->ssm_registration_number }}</div>@endif
                    </div>
                @endif
                <div class="d-flex gap-2 mt-3" aria-label="Social media">
                    <a class="btn btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                        href="{{ $companyProfile->facebook_url ?: 'https://www.facebook.com/omgcomputer' }}"
                        target="_blank" rel="noopener noreferrer" aria-label="Facebook" style="width: 2.5rem; height: 2.5rem;">
                        <span class="bi bi-facebook" aria-hidden="true"></span>
                    </a>
                    <a class="btn btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                        href="{{ $companyProfile->instagram_url ?: 'https://www.instagram.com/omg_computer/' }}"
                        target="_blank" rel="noopener noreferrer" aria-label="Instagram" style="width: 2.5rem; height: 2.5rem;">
                        <span class="bi bi-instagram" aria-hidden="true"></span>
                    </a>
                    @if ($companyProfile->tiktok_url)
                        <a class="btn btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                            href="{{ $companyProfile->tiktok_url }}"
                            target="_blank" rel="noopener noreferrer" aria-label="TikTok" style="width: 2.5rem; height: 2.5rem;">
                            <span class="bi bi-tiktok" aria-hidden="true"></span>
                        </a>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="{{ route('shop.public') }}">{{ __('Browse the shop') }}</a>
                    <a class="btn btn-primary btn-sm rounded-pill px-3" href="{{ route('bookings.create') }}">{{ __('Book a service') }}</a>
                    <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="{{ route('repairs.track.form') }}">{{ __('Track a repair') }}</a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row gy-4">
                    <div class="col-6 col-md-3">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">{{ __('Company') }}</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ route('about') }}">{{ __('About us') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#contact">{{ __('Contact') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#help">{{ __('Help center') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#faq">{{ __('Frequently asked questions') }}</a></li>
                            <li><a class="link-600" href="{{ route('bookings.create') }}">{{ __('Book an appointment') }}</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md-3">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">{{ __('Services') }}</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">{{ __('PC diagnosis & repair') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">{{ __('Upgrades & software support') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('shop.public', ['type' => 'ready_built_pc']) }}">{{ __('Ready-built custom PCs') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ url('/') }}#services">{{ __('Website design & development') }}</a></li>
                            <li><a class="link-600" href="{{ route('website-previews.index') }}">{{ __('Website previews') }}</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md-3">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">{{ __('Quick links') }}</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ route('shop.public') }}">{{ __('Shop parts & ready-built PCs') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('repairs.track.form') }}">{{ __('Track a repair') }}</a></li>
                            @auth
                                <li><a class="link-600" href="{{ route('dashboard') }}">{{ __('Go to panel') }}</a></li>
                            @else
                                <li><a class="link-600" href="{{ route('login') }}">{{ __('Sign in') }}</a></li>
                                <li><a class="link-600" href="{{ route('register') }}">{{ __('Create an account') }}</a></li>
                            @endauth
                        </ul>
                    </div>
                    <div class="col-6 col-md-3">
                        <h2 class="h6 text-uppercase text-white opacity-85 mb-3">{{ __('Policies') }}</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a class="link-600" href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('refund-policy') }}">{{ __('Refund policy') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('shipping-policy') }}">{{ __('Shipping & returns policy') }}</a></li>
                            <li class="mb-2"><a class="link-600" href="{{ route('terms') }}">{{ __('Terms of service') }}</a></li>
                            <li><a class="link-600" href="{{ route('data-deletion.instructions') }}">{{ __('Data deletion') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
            <p class="text-600 fs-10 mb-0">&copy; {{ date('Y') }} OMG Empire (OMG Computer). All rights reserved.</p>
         </div>
    </div>
</footer>
