@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <x-auth-card
        title="Verify your email"
        description="One last step before you get started."
    >
        <div class="text-center mb-4">
            <span class="bi bi-envelope-paper text-primary fs-3" aria-hidden="true"></span>
        </div>

        <p class="mb-3">
            We sent a verification link to
            <strong>{{ auth()->user()->email }}</strong>.
            Open that email and follow the link to activate your account.
        </p>
        <p class="text-body-secondary small mb-4">
            If it isn’t in your inbox, check your spam folder or request another link below.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success" role="status">
                A fresh verification link has been sent to your email address.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
            @csrf
            <button class="btn btn-primary w-100" type="submit">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button class="btn btn-link link-secondary text-decoration-none" type="submit">Sign out</button>
        </form>
    </x-auth-card>
@endsection
