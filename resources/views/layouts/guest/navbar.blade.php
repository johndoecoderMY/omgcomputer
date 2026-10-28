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
                <li class="nav-item"><a class="nav-link" href="{{ route('shop.public') }}">Shop</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#website-pricing">Packages</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#how-it-works">How it works</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#help">Help</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#faq">Q &amp; A</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#contact">Contact</a></li>
                <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm rounded-pill px-3" href="{{ route('bookings.create') }}">Book a service</a></li>
                @auth
                    @if (auth()->user()->hasRole('customer'))
                        @php($cartItemCount = collect(session('shop_cart', []))->sum())
                    @endif
                    <li class="nav-item dropdown ms-lg-3">
                        <button class="nav-link dropdown-toggle border-0 bg-transparent" id="guest-account-menu" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="bi bi-person-circle me-1" aria-hidden="true"></span>{{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="guest-account-menu">
                            <li><span class="dropdown-item-text small text-body-secondary">Signed in as {{ auth()->user()->email }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            @if (auth()->user()->hasRole('customer'))
                                <li><a class="dropdown-item" href="{{ route('shop.my-orders.index') }}"><span class="bi bi-bag-check me-2" aria-hidden="true"></span>My orders</a></li>
                                <li><a class="dropdown-item" href="{{ route('shop.tracking') }}"><span class="bi bi-truck me-2" aria-hidden="true"></span>Track parcel</a></li>
                                <li><a class="dropdown-item" href="{{ route('shop.cart') }}" aria-label="Shopping cart, {{ $cartItemCount }} {{ \Illuminate\Support\Str::plural('item', $cartItemCount) }}"><span class="bi bi-cart3 me-2" aria-hidden="true"></span>Cart @if ($cartItemCount > 0)<span class="badge rounded-pill text-bg-primary ms-1" data-cart-count>{{ $cartItemCount > 99 ? '99+' : $cartItemCount }}</span>@endif</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><span class="bi bi-speedometer2 me-2" aria-hidden="true"></span>Go to panel</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit"><span class="bi bi-box-arrow-right me-2" aria-hidden="true"></span>Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm rounded-pill px-3" href="{{ route('login') }}">Sign in</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
