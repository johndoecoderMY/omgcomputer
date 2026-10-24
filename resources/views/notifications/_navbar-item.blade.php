<div class="list-group-item p-0" data-notification-item>
    <a class="notification notification-flush {{ $notification->read_at ? '' : 'notification-unread' }}"
        href="{{ route('notifications.read', $notification) }}">
        <div class="notification-avatar">
            <div class="avatar avatar-2xl me-3">
                <div class="avatar-name rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                    <span class="bi bi-bell" aria-hidden="true"></span>
                </div>
            </div>
        </div>
        <div class="notification-body">
            <p class="mb-1"><strong>{{ $notification->data['title'] ?? 'Workspace update' }}</strong>
                {{ $notification->data['message'] ?? '' }}</p>
            <span class="notification-time">
                <span class="bi bi-bell me-2 text-primary" aria-hidden="true"></span>
                <time datetime="{{ $notification->created_at->toIso8601String() }}">
                    {{ $notification->created_at->diffForHumans() }}
                </time>
            </span>
        </div>
    </a>
</div>
