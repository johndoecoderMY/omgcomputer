@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="row min-vh-100 flex-center g-0">
        <div class="col-lg-8 col-xxl-5 py-3 position-relative">
            <img class="bg-auth-circle-shape" src="../../../assets/img/icons/spot-illustrations/bg-shape.png" alt="" width="250">
            <img class="bg-auth-circle-shape-2" src="../../../assets/img/icons/spot-illustrations/shape-1.png" alt="" width="150">

            <div class="card overflow-hidden z-1">
                <div class="card-body p-0">
                    <div class="row g-0 h-100">
                        <div class="col-md-5 text-center bg-card-gradient">
                            <div class="position-relative p-4 pt-md-5 pb-md-7" data-bs-theme="light">
                                <div class="bg-holder bg-auth-card-shape" style="background-image:url(../../../assets/img/icons/spot-illustrations/half-circle.png);"></div>
                                <div class="z-1 position-relative">
                                    <a class="link-light mb-4 font-sans-serif fs-5 d-inline-block fw-bolder" href="{{ route('login') }}">falcon</a>
                                    <p class="opacity-75 text-white">With the power of Falcon, you can now focus only on functionaries for your digital products, while leaving the UI design on us!</p>
                                </div>
                            </div>

                            <div class="mt-3 mb-4 mt-md-4 mb-md-5" data-bs-theme="light">
                                <p class="text-white">Don't have an account?<br><a class="text-decoration-underline link-light" href="{{ route('register') }}">Get started!</a></p>
                                <p class="mb-0 mt-4 mt-md-5 fs-10 fw-semi-bold text-white opacity-75">Read our <a class="text-decoration-underline text-white" href="{{ route('login') }}#!">terms</a> and <a class="text-decoration-underline text-white" href="{{ route('login') }}#!">conditions</a></p>
                            </div>
                        </div>

                        <div class="col-md-7 d-flex flex-center">
                            <div class="p-4 p-md-5 flex-grow-1">
                                <div class="row flex-between-center">
                                    <div class="col-auto">
                                        <h3>Account Login</h3>
                                    </div>
                                </div>

                                @if ($errors->any())
                                    <div class="alert alert-danger mt-3 mb-0" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (session('status'))
                                    <div class="alert alert-success mt-3 mb-0" role="alert">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login') }}" class="mt-4">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email address</label>
                                        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label" for="password">Password</label>
                                        </div>
                                        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password">
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="row flex-between-center">
                                        <div class="col-auto">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                <label class="form-check-label mb-0" for="remember">Remember me</label>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <a class="fs-10" href="{{ route('password.request') }}">Forgot Password?</a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <button class="btn btn-primary d-block w-100 mt-3" type="submit">Log in</button>
                                    </div>
                                </form>

                                <div class="position-relative mt-4">
                                    <hr>
                                    <div class="divider-content-center">or log in with</div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-sm-6">
                                        <a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#"><span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span> google</a>
                                    </div>
                                    <div class="col-sm-6">
                                        <a class="btn btn-outline-facebook btn-sm d-block w-100" href="#"><span class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection