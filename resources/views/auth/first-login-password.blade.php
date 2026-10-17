@extends('layouts.app')

@section('title', 'Change your password')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7 col-xl-5">
            <div class="card">                <div class="card-header bg-body">
                    <h1 class="h4 mb-1">Choose a new password</h1>
                    <p class="small text-body-secondary mb-0">For your security, change the temporary password before continuing.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.first-login.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Current password</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password"
                                type="password" name="current_password" autocomplete="current-password" required autofocus>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">New password</label>
                            <input class="form-control @error('password') is-invalid @enderror" id="password"
                                type="password" name="password" autocomplete="new-password" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Update password and continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
