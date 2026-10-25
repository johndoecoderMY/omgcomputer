<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">
    <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button"
        data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
        aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span
                class="toggle-line"></span></span></button>
    <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
        <div class="d-flex align-items-center">
            <img class="me-2 brand-logo brand-logo-light" src="{{ asset('assets/img/omg/logoex2.png') }}"
                alt="OMG Computer logo" width="80" />
            <img class="me-2 brand-logo brand-logo-dark" src="{{ asset('assets/img/omg/logoex2w.png') }}"
                alt="" aria-hidden="true" width="80" />
        </div>
    </a>
    <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
        <li class="nav-item ps-2 pe-0 px-2">
            <div class="dropdown theme-control-dropdown"><a
                    class="nav-link d-flex align-items-center dropdown-toggle fs-9 pe-1 py-0"
                    href="navbar-vertical.html#" role="button" id="themeSwitchDropdown" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false"><span class="bi bi-sun fs-7"
                        data-theme-dropdown-toggle-icon="light"></span><span
                        class="bi bi-moon fs-7 d-none"
                        data-theme-dropdown-toggle-icon="dark"></span><span class="bi bi-circle-half fs-7 d-none"
                        data-theme-dropdown-toggle-icon="auto"></span></a>
                <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-0 mt-3"
                    aria-labelledby="themeSwitchDropdown">
                    <div class="bg-white dark__bg-1000 rounded-2 py-2"><button
                            class="dropdown-item d-flex align-items-center gap-2" type="button" value="light"
                            data-theme-control="theme"><span class="bi bi-sun"></span>Light<span
                                class="bi bi-check dropdown-check-icon ms-auto text-600"></span></button>
                        <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark"
                            data-theme-control="theme"><span class="bi bi-moon"></span>Dark<span
                                class="bi bi-check dropdown-check-icon ms-auto text-600"></span></button>
                        <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="auto"
                            data-theme-control="theme"><span class="bi bi-circle-half"></span>Auto<span
                                class="bi bi-check dropdown-check-icon ms-auto text-600"></span></button>
                    </div>
                </div>
            </div>
        </li>

        @if (auth()->user()->hasRole('customer'))
            <li class="nav-item d-none d-sm-block">
                <a class="nav-link px-2" href="{{ route('shop.tracking') }}" title="Track a parcel">
                    <span class="bi bi-geo-alt me-1" aria-hidden="true"></span>Track parcel
                </a>
            </li>
            <li class="nav-item d-none d-sm-block">
                <a class="nav-link px-2" href="{{ route('shop.my-orders.index') }}" title="My shop orders">
                    <span class="bi bi-receipt me-1" aria-hidden="true"></span>My orders
                </a>
            </li>
            @php
                $cartItemCount = collect(session('shop_cart', []))->sum();
            @endphp
            <li class="nav-item d-none d-sm-block">
                <a class="nav-link px-0 notification-indicator notification-indicator-warning notification-indicator-fill"
                    href="{{ route('shop.cart') }}"
                    aria-label="Shopping cart, {{ $cartItemCount }} {{ \Illuminate\Support\Str::plural('item', $cartItemCount) }}"
                    title="Shopping cart" data-cart-count>
                    <span class="bi bi-cart3" style="font-size: 22px;" aria-hidden="true"></span>
                    <span class="notification-indicator-number" style="width: auto; min-width: 1rem; padding: 0 .15rem;" aria-hidden="true">
                        {{ $cartItemCount > 99 ? '99+' : $cartItemCount }}
                    </span>
                </a>
            </li>
        @endif

        @auth
            @include('notifications._navbar')
        @endauth

        <li class="nav-item dropdown">
            <a class="nav-link pe-0 ps-2" href="{{ route('profile.edit') }}" id="navbarDropdownUser" role="button"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                aria-label="Open account menu for {{ auth()->user()->name }}">
                <div class="avatar avatar-xl">
                    @if (auth()->user()?->profile_photo_url)
                        <img class="rounded-circle" src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }} profile photo">
                    @else
                        <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                            <span>{{ str(auth()->user()?->name ?? 'U')->substr(0, 1)->upper() }}</span>
                        </div>
                    @endif
                </div>
            </a>
            <div class="dropdown-menu dropdown-caret dropdown-menu-end py-0"
                aria-labelledby="navbarDropdownUser">
                <div class="bg-white dark__bg-1000 rounded-2 py-2">
                    <div class="px-3 py-2">
                        <div class="fw-semibold text-body">{{ auth()->user()->name }}</div>
                        <div class="small text-body-secondary text-break">{{ auth()->user()->email }}</div>
                    </div>
                    <div class="dropdown-divider"></div>
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
    </ul>
</nav>