<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.guest.head')

<body>
    <main class="main" id="top">
        @include('layouts.guest.navbar')


        @yield('content')

        @include('layouts.guest.footer')

        @stack('modal')


       

    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->



    @include('layouts.guest.scripts')
    @stack('scripts')

</body>

</html>