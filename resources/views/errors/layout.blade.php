<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title') · OMG Computer</title>
    <link rel="stylesheet" href="{{ asset('assets/css/theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/user.min.css') }}">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f5f7fa; }
        .error-card { width: min(100% - 2rem, 38rem); }
        .error-code { color: #2c7be5; font-size: clamp(4rem, 16vw, 8rem); font-weight: 800; line-height: 1; }
    </style>
</head>
<body>
    <main class="card error-card shadow-sm">
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
</body>
</html>
