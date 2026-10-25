@extends('layouts.auth')

@section('title', 'Verify email and set password')

@section('content')
    <x-auth-card
        title="Verify your email and set a password"
        description="Your OMG Computer membership has been created. Confirm this email address by choosing a password for your account."
    >
        <p class="small text-body-secondary">{{ $user->email }}</p>
        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="password">Choose a password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    type="password" required autocomplete="new-password" autofocus>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation"
                    type="password" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary w-100" type="submit">Verify email and set password</button>
        </form>
    </x-auth-card>
@endsection
