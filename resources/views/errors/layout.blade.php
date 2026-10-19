<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title') · OMG Computer</title>
    <link rel="stylesheet" href="{{ asset('assets/css/theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/user.min.css') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicons/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicons/favicon.ico') }}">
   
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f5f7fa; }
        .error-layout { width: min(100% - 2rem, 58rem); display: grid; grid-template-columns: 13rem minmax(0, 1fr); gap: 1rem; align-items: stretch; }
        .error-card { width: 100%; }
        .error-code { color: #2c7be5; font-size: clamp(4rem, 16vw, 8rem); font-weight: 800; line-height: 1; }
        @media (max-width: 767.98px) { .error-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="error-layout">
        @auth
            @unless (auth()->user()->hasRole('customer'))
                <aside class="card shadow-sm p-2" aria-label="Error page previews">
                    <div class="card-body">
                        <h2 class="h6 text-primary">Error page previews</h2>
                        <nav class="nav flex-column">
                            @foreach (['403' => 'Forbidden', '404' => 'Not found', '419' => 'Session expired', '429' => 'Too many requests', '500' => 'Server error', '503' => 'Unavailable'] as $code => $label)
                                <a class="nav-link px-0" href="{{ route('error-pages.preview', $code) }}">{{ $code }} · {{ $label }}</a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endunless
        @endauth
        <main class="card error-card shadow-sm p-2">
            <div class="card-body p-4 p-md-5 text-center">
                <a class="d-inline-block mb-4" href="{{ url('/') }}">
                    <img src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer" width="100">
                </a>
                <p class="error-code mb-3">@yield('code')</p>
                <h1 class="h3 mb-2">@yield('heading')</h1>
                <p class="text-body-secondary mb-4">@yield('message')</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    @auth
                        <a class="btn btn-primary" href="{{ route('dashboard') }}">Go to dashboard</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">Sign in</a>
                    @endauth
                    <button class="btn btn-outline-secondary" type="button" onclick="history.back()">Go back</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
