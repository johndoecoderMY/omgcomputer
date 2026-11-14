<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">


@include('layouts.app.head')

<body
    @auth
        data-notifications-user="{{ auth()->id() }}"
        data-staff-pwa="{{ auth()->user()->hasAnyRole(['admin', 'member']) ? 'true' : 'false' }}"
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

            <div class="modal fade" id="project-ajax-modal" tabindex="-1" aria-labelledby="project-ajax-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="project-ajax-modal-title">Project workspace</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" data-project-ajax-modal-body></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @include('layouts.app.toasts')
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->


    @include('layouts.app.scripts')
    @stack('scripts')

</body>

</html>