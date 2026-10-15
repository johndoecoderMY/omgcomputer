<!doctype html>
<html lang="en">
@include('layouts.app.head')
<body>
    <main class="container py-3 py-lg-5">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <a class="d-inline-flex align-items-center" href="{{ url('/') }}"><img src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer" width="88"></a>
            <a class="btn btn-outline-secondary" href="{{ route('login') }}">Staff sign in</a>
        </header>
        @yield('content')
        <footer class="small text-body-secondary mt-4">OMG Computer · Kampar, Perak · Appointment times are Malaysia time (MYT).</footer>
    </main>
    @include('layouts.app.scripts')
    <script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
    @stack('scripts')
</body>
</html>