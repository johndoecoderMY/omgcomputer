@extends('layouts.auth')

@section('title', 'Create an account')

@section('content')
    <x-auth-card
        title="Create your account"
        description="Register to keep track of your repairs and projects."
    >
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text"
                    value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email"
                    value="{{ old('email') }}" required autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    type="password" required autocomplete="new-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">We’ll send a verification link to your email before you can access your account.</div>
            </div>

            <button class="btn btn-primary w-100" type="submit">Create account</button>
        </form>

    </x-auth-card>
@endsection
