<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.app.head')

<body
    @auth
        data-notifications-user="{{ auth()->id() }}"
    @endauth
    data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
    data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
    data-notification-read-prefix="{{ url('/notifications') }}">
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
    @include('layouts.app.toasts')
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->


    @include('layouts.app.scripts')
    @stack('scripts')

</body>

</html>