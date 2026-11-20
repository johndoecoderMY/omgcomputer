@extends('layouts.auth')

@section('title', __('Verify your email'))

@section('content')
    <x-auth-card
        :title="__('Verify your email')"
        :description="__('One last step before you get started.')"
    >
        <div class="text-center mb-4">
            <span class="bi bi-envelope-paper text-primary fs-3" aria-hidden="true"></span>
        </div>

        <p class="mb-3">
            {{ __('We sent a verification link to :email.', ['email' => auth()->user()->email]) }}
            {{ __('Open that email and follow the link to activate your account.') }}
        </p>
        <p class="text-body-secondary small mb-4">
            {{ __('If it isn’t in your inbox, check your spam folder or request another link below.') }}
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success" role="status">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
            @csrf
            <button class="btn btn-primary w-100" type="submit">{{ __('Resend verification email') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button class="btn btn-link link-secondary text-decoration-none" type="submit">{{ __('Sign out') }}</button>
        </form>
    </x-auth-card>
@endsection
