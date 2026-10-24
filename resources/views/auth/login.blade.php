@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <x-auth-card
        title="Welcome back"
        description="Sign in to manage your repairs, projects, and account."
    >
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email"
                    value="{{ old('email') }}" required autocomplete="username" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label" for="password">Password</label>
                    <a class="small" href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    type="password" required autocomplete="current-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="remember" name="remember"
                    {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>

        <p class="text-center text-body-secondary small mt-4 mb-0">
            New here? <a href="{{ route('register') }}">Create an account</a>
        </p>

    </x-auth-card>
@endsection
