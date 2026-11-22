<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top py-1">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <picture>
                <source type="image/webp" srcset="{{ asset('assets/img/omg/logoex2w-120.webp') }} 1x, {{ asset('assets/img/omg/logoex2w-240.webp') }} 2x">
                <img src="{{ asset('assets/img/omg/logoex2w.png') }}" alt="OMG Computer" width="91" height="48" class="me-2">
            </picture>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guestNavbar" aria-controls="guestNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="guestNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item my-2 my-lg-0 ms-lg-2">
                    <a class="btn btn-outline-light btn-sm rounded-pill px-3 py-1" href="{{ auth()->check() && auth()->user()->hasRole('customer') ? route('shop.panel') : route('shop.public') }}">
                        <span class="bi bi-shop me-1" aria-hidden="true"></span>{{ __('Shop') }}
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link px-lg-2" href="{{ url('/') }}#services">{{ __('Services') }}</a></li>
                <li class="nav-item"><a class="nav-link px-lg-2" href="{{ url('/') }}#website-pricing">{{ __('Website') }}</a></li>
                <li class="nav-item"><a class="nav-link px-lg-2" href="{{ url('/') }}#faq">{{ __('Q&A') }}</a></li>
                <li class="nav-item"><a class="nav-link px-lg-2" href="{{ url('/') }}#contact">{{ __('Contact Us') }}</a></li>
                <li class="nav-item my-2 my-lg-0 ms-lg-4"><a class="btn btn-primary btn-sm rounded-pill px-3 py-1" href="{{ route('bookings.create') }}">{{ __('Book a service') }}</a></li>
                <li class="nav-item dropdown my-2 my-lg-0 ms-lg-2">
                    <button class="nav-link dropdown-toggle border-0 bg-transparent" id="guest-settings-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Settings') }}">
                        <span class="bi bi-gear" aria-hidden="true"></span>
                        <span class="d-lg-none ms-1">{{ __('Settings') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end py-0" aria-labelledby="guest-settings-menu">
                        <div class="bg-white dark__bg-1000 rounded-2 py-2">
                            <div class="dropdown-header text-uppercase">{{ __('Language') }}</div>
                            @foreach (['en' => 'English', 'ms' => 'Bahasa Melayu', 'zh_CN' => '中文'] as $locale => $language)
                            <form method="POST" action="{{ route('language.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $locale }}">
                                <button class="dropdown-item d-flex align-items-center" type="submit" @if (app()->getLocale() === $locale) aria-current="true" @endif>
                                    {{ $language }}
                                    @if (app()->getLocale() === $locale)
                                        <span class="bi bi-check ms-auto" aria-hidden="true"></span>
                                    @endif
                                </button>
                            </form>
                            @endforeach
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header text-uppercase">{{ __('Appearance') }}</div>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="light" data-theme-control="theme">
                                <span class="bi bi-sun" aria-hidden="true"></span>{{ __('Light') }}
                                <span class="bi bi-check dropdown-check-icon ms-auto text-600" aria-hidden="true"></span>
                            </button>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark" data-theme-control="theme">
                                <span class="bi bi-moon" aria-hidden="true"></span>{{ __('Dark') }}
                                <span class="bi bi-check dropdown-check-icon ms-auto text-600" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </li>
                @auth
                    @if (auth()->user()->hasRole('customer'))
                        @php($cartItemCount = collect(session('shop_cart', []))->sum())
                    @endif
                    @include('notifications._navbar')
                    <li class="nav-item dropdown my-2 my-lg-0 ms-lg-2">
                        <button class="nav-link dropdown-toggle border-0 bg-transparent" id="guest-account-menu" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="bi bi-person-circle me-1" aria-hidden="true"></span>{{ auth()->user()->name }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-end py-0" aria-labelledby="guest-account-menu">
                            <div class="rounded-2 py-2">
                                <div class="px-3 py-2">
                                    <div class="fw-semibold text-body">{{ auth()->user()->name }}</div>
                                    <div class="small text-body-secondary text-break">{{ auth()->user()->email }}</div>
                                </div>
                                <div class="dropdown-divider"></div>
                                @if (auth()->user()->hasRole('customer'))
                                    <a class="dropdown-item" href="{{ route('shop.tracking') }}">
                                        <span class="bi bi-geo-alt me-2 text-body-secondary" aria-hidden="true"></span>Track parcel
                                    </a>
                                    <a class="dropdown-item" href="{{ route('shop.my-orders.index') }}">
                                        <span class="bi bi-receipt me-2 text-body-secondary" aria-hidden="true"></span>My orders
                                    </a>
                                    <a class="dropdown-item" href="{{ route('shop.cart') }}" aria-label="Shopping cart, {{ $cartItemCount }} {{ \Illuminate\Support\Str::plural('item', $cartItemCount) }}">
                                        <span class="bi bi-cart3 me-2 text-body-secondary" aria-hidden="true"></span>Cart
                                        <span class="badge rounded-pill text-bg-primary ms-1" data-cart-count>{{ $cartItemCount > 99 ? '99+' : $cartItemCount }}</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                @else
                                    <a class="dropdown-item" href="{{ route('email.inbox') }}">
                                        <span class="bi bi-envelope me-2 text-body-secondary" aria-hidden="true"></span>Email inbox
                                    </a>
                                    @if (auth()->user()->hasRole('admin') || auth()->user()->can('projects.calendar.manage'))
                                        <a class="dropdown-item" href="{{ route('calendar') }}">
                                            <span class="bi bi-calendar3 me-2 text-body-secondary" aria-hidden="true"></span>Calendar
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('tasks.index') }}">
                                        <span class="bi bi-list-task me-2 text-body-secondary" aria-hidden="true"></span>Tasks
                                    </a>
                                    <div class="dropdown-divider"></div>
                                @endif
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <span class="bi bi-person me-2 text-body-secondary" aria-hidden="true"></span>Profile and account
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">
                                        <span class="bi bi-box-arrow-right me-2" aria-hidden="true"></span>Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @else
                    <li class="nav-item my-2 mx-lg-0 ms-lg-2"><a class="btn btn-outline-light btn-sm rounded-pill px-3 py-1" href="{{ route('login') }}">{{ __('Sign in') }}</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
