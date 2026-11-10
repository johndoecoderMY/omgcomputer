<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.guest.head')

<body>
    @include('layouts.guest.navbar')

    @unless (request()->getPathInfo() === '/')
        <div class="container" style="padding-top: 5rem; padding-bottom: 1rem;">
            <a class="d-inline-flex align-items-center text-primary text-decoration-none mb-1" href="{{ url('/') }}">
                <span class="bi bi-arrow-left me-2" aria-hidden="true"></span>Back to OMG Computer
            </a>
        </div>
    @endunless

    <main class="main" id="top">
        @yield('content')

        @include('layouts.guest.footer')

        @stack('modal')


       

    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->



    @include('layouts.guest.scripts')
    @stack('scripts')

</body>

</html>