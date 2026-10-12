<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.guest.head')

<body>
    <main class="main" id="top">

        <div class="container-fluid">
            @yield('content')

        </div>
        @stack('modal')




    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->



    @include('layouts.guest.scripts')

</body>

</html>