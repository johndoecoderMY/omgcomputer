@php
    $notificationUser = auth()->user();
    $unreadNotificationCount = $notificationUser->unreadNotifications()->count();
    $recentNotifications = $notificationUser->notifications()->latest()->limit(6)->get();
@endphp

<li class="nav-item dropdown d-flex align-items-center">
    <a class="nav-link position-relative d-flex align-items-center justify-content-center px-2 py-0 fa-icon-wait"
        href="{{ route('notifications.index') }}" id="navbarDropdownNotification" role="button"
        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Notifications"
        data-hide-on-body-scroll="data-hide-on-body-scroll">
        <span class="fas fa-bell d-block" data-fa-transform="shrink-6" style="font-size: 33px; line-height: 1;" aria-hidden="true"></span>
        <span id="unread-notifications-count" class="notification-count {{ $unreadNotificationCount ? '' : 'd-none' }}"
            aria-live="polite" aria-label="{{ $unreadNotificationCount }} unread notifications">
            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
        </span>
    </a>
    <div class="dropdown-menu dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg"
        aria-labelledby="navbarDropdownNotification">
        <div class="card card-notification shadow-none">
            <div class="card-header">
                <div class="row justify-content-between align-items-center">
                    <div class="col-auto"><h2 class="card-header-title h6 mb-0">Notifications</h2></div>
                    <div class="col-auto ps-0 ps-sm-3">
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button class="card-link fw-normal btn btn-link btn-sm p-0" type="submit"
                                @disabled($unreadNotificationCount === 0)>Mark all as read</button>
                        </form>
                    </div>
                </div>
            </div>
            <div id="navbar-notifications-list" class="scrollbar-overlay notification-list" style="max-height: 19rem;">
                <div class="list-group list-group-flush fw-normal fs-10">
                    @php
                        $unreadRecentNotifications = $recentNotifications->filter(fn ($notification) => ! $notification->read_at);
                        $readRecentNotifications = $recentNotifications->filter(fn ($notification) => $notification->read_at);
                    @endphp
                    @if ($unreadRecentNotifications->isNotEmpty())
                        <div class="list-group-title border-bottom px-3 py-2" data-notification-new-title>NEW</div>
                        @foreach ($unreadRecentNotifications as $notification)
                            @include('notifications._navbar-item', ['notification' => $notification])
                        @endforeach
                    @endif
                    @if ($readRecentNotifications->isNotEmpty())
                        <div class="list-group-title border-bottom px-3 py-2">EARLIER</div>
                        @foreach ($readRecentNotifications as $notification)
                            @include('notifications._navbar-item', ['notification' => $notification])
                        @endforeach
                    @elseif ($recentNotifications->isEmpty())
                        <p id="navbar-notifications-empty" class="small text-body-secondary text-center py-4 mb-0">
                            You’re all caught up.
                        </p>
                    @endif
                </div>
            </div>
            <div class="card-footer text-center">
                <a class="card-link d-block" href="{{ route('notifications.index') }}">View all</a>
            </div>
        </div>
    </div>
</li>

@once
    <style>
        #navbarDropdownNotification .notification-count {
            position: absolute;
            top: -.2rem;
            right: -.55rem;
            display: grid;
            min-width: 1.15rem;
            height: 1.15rem;
            place-items: center;
            padding: 0 .22rem;
            border: 2px solid var(--falcon-body-bg);
            border-radius: 999px;
            background-color: var(--falcon-danger);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
    </style>
@endonce
