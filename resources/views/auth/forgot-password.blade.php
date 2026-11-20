@extends('layouts.auth')

@section('title', __('Reset your password'))

@section('content')
    <x-auth-card
        :title="__('Reset your password')"
        :description="__('Enter your account email and we’ll send you a link to choose a new password.')"
    >
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label" for="email">{{ __('Email address') }}</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email"
                    value="{{ old('email') }}" required autocomplete="username" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">{{ __('Email password reset link') }}</button>
        </form>

        <p class="text-center mt-4 mb-0">
            <a href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
        </p>
    </x-auth-card>
@endsection
