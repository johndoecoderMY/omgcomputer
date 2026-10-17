@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Workspace" title="Notifications" description="Updates about assigned tasks and project events.">
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-outline-primary" type="submit">Mark all as read</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card p-2">        @forelse ($notifications as $notification)
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3 {{ $notification->read_at ? '' : 'bg-body-tertiary' }}"
                href="{{ route('notifications.read', $notification) }}">
                <div>
                    <h2 class="h6 mb-1">{{ $notification->data['title'] ?? 'Workspace update' }}</h2>
                    <p class="mb-1 text-body-secondary">{{ $notification->data['message'] ?? '' }}</p>
                    <time class="small text-body-secondary" datetime="{{ $notification->created_at->toIso8601String() }}">
                        {{ $notification->created_at->diffForHumans() }}
                    </time>
                </div>
                @unless ($notification->read_at)
                    <span class="badge text-bg-primary">New</span>
                @endunless
            </a>
        @empty
            <div class="card-body text-center py-5">
                <i class="fas fa-bell-slash text-body-secondary fs-2 mb-3" aria-hidden="true"></i>
                <p class="mb-0 text-body-secondary">You’re all caught up. New workspace updates will appear here.</p>
            </div>
        @endforelse
        @if ($notifications->hasPages())
            <div class="card-footer bg-body">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
@endsection
