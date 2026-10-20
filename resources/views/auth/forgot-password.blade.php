@extends('layouts.auth')

@section('title', 'Reset your password')

@section('content')
    <x-auth-card
        title="Reset your password"
        description="Enter your account email and we’ll send you a link to choose a new password."
    >
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email"
                    value="{{ old('email') }}" required autocomplete="username" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">Email password reset link</button>
        </form>

        <p class="text-center mt-4 mb-0">
            <a href="{{ route('login') }}">Back to sign in</a>
        </p>
    </x-auth-card>
@endsection
