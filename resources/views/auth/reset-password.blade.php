@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')
    <x-auth-card
        title="Choose a new password"
        description="Enter the email address for your account and choose a new password."
    >
        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email"
                    value="{{ old('email', $request->email) }}" required autocomplete="username" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">New password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    type="password" required autocomplete="new-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">Reset password</button>
        </form>
    </x-auth-card>
@endsection
