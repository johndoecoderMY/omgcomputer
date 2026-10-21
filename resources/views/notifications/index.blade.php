@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Workspace" title="Notifications" description="Updates about assigned tasks and project events." />

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card overflow-hidden">
        <div class="card-header bg-body-tertiary">
            <div class="row flex-between-center">
                <div class="col-sm-auto">
                    <h2 class="h5 mb-1 mb-md-0">Your Notifications</h2>
                </div>
                <div class="col-sm-auto fs-10">
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="font-sans-serif btn btn-link p-0" type="submit"
                            @disabled($unreadNotificationCount === 0)>
                            Mark all as read
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body fs-10 p-0">
            @forelse ($notifications as $notification)
                <a class="notification rounded-0 border-x-0 border-300 {{ $notification->read_at ? 'border-bottom-0' : 'notification-unread border-bottom-0' }}"
                    href="{{ route('notifications.read', $notification) }}">
                    <div class="notification-avatar">
                        <div class="avatar avatar-xl me-3">
                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                                <span class="fas fa-bell" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>
                    <div class="notification-body">
                        <p class="mb-1"><strong>{{ $notification->data['title'] ?? 'Workspace update' }}</strong>
                            {{ $notification->data['message'] ?? '' }}</p>
                        <span class="notification-time">
                            <span class="fas fa-bell me-2 text-primary" aria-hidden="true"></span>
                            <time datetime="{{ $notification->created_at->toIso8601String() }}">
                                {{ $notification->created_at->diffForHumans() }}
                            </time>
                        </span>
                    </div>
                </a>
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash text-body-secondary fs-2 mb-3" aria-hidden="true"></i>
                    <p class="mb-0 text-body-secondary">You’re all caught up. New workspace updates will appear here.</p>
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer bg-body">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
@endsection
