@php
    $notificationUser = auth()->user();
    $unreadNotificationCount = $notificationUser->unreadNotifications()->count();
    $recentNotifications = $notificationUser->notifications()->latest()->limit(6)->get();
@endphp

<li class="nav-item dropdown">
    <a class="nav-link position-relative px-2" href="#" id="navbarDropdownNotification" role="button"
        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
        <span class="fas fa-bell fs-6" aria-hidden="true"></span>
        <span id="unread-notifications-count" class="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle notification-count {{ $unreadNotificationCount ? '' : 'd-none' }}">
            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-card dropdown-menu-notification"
        aria-labelledby="navbarDropdownNotification">
        <div class="card card-notification shadow-none">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Notifications</h2>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-link btn-sm p-0" type="submit" @disabled($unreadNotificationCount === 0)>Mark all read</button>
                </form>
            </div>
            <div id="navbar-notifications-list" class="list-group list-group-flush notification-list">
                @forelse ($recentNotifications as $notification)
                    <a class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'bg-body-tertiary' }}"
                        href="{{ route('notifications.read', $notification) }}" data-notification-item>
                        <span class="d-flex justify-content-between align-items-start gap-2">
                            <strong class="small">{{ $notification->data['title'] ?? 'Workspace update' }}</strong>
                            @unless ($notification->read_at)
                                <span class="badge text-bg-primary">New</span>
                            @endunless
                        </span>
                        <span class="small text-body-secondary d-block mt-1">{{ $notification->data['message'] ?? '' }}</span>
                        <time class="small text-body-tertiary" datetime="{{ $notification->created_at->toIso8601String() }}">
                            {{ $notification->created_at->diffForHumans() }}
                        </time>
                    </a>
                @empty
                    <p id="navbar-notifications-empty" class="small text-body-secondary text-center py-4 mb-0">
                        You’re all caught up.
                    </p>
                @endforelse
            </div>
            <div class="card-footer text-center">
                <a class="small" href="{{ route('notifications.index') }}">View all notifications</a>
            </div>
        </div>
    </div>
</li>
