<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.app.head')

<body>
    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
        <div class="container-fluid" data-layout="container">

            @include('layouts.app.sidebar')

            <div class="content">

                @include('layouts.app.navbar')
                @yield('content')


                @include('layouts.app.footer')


            </div>
            @stack('modal')


        </div>
    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->


    @include('layouts.app.scripts')
    @stack('scripts')

</body>

</html>