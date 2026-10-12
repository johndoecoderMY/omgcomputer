<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.app.head')

<body>
    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
        <div class="container" data-layout="container">
    
            @include('layouts.app.navbar')

            <div class="content">

                @include('layouts.app.sidebar')
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