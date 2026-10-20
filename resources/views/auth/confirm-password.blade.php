@extends('layouts.auth')

@section('title', 'Confirm your password')

@section('content')
    <x-auth-card
        title="Confirm your password"
        description="This is a secure area. Please confirm your password before continuing."
    >
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    type="password" required autocomplete="current-password" autofocus>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">Confirm password</button>
        </form>
    </x-auth-card>
@endsection
