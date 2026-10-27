@extends('layouts.app')

@section('title', $message['subject'])

@section('content')
<div class="container-fluid py-3">
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap align-items-center">
                <a class="btn btn-falcon-default btn-sm" href="{{ route('email.inbox') }}" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Back to inbox" title="Back to inbox">
                    <span class="bi bi-arrow-left" aria-hidden="true"></span>
                </a>
                <span class="mx-1 mx-sm-2 text-300" aria-hidden="true">|</span>
                <form method="POST" action="{{ route('email.action', [$message['uid'], 'archive']) }}">@csrf
                    <button class="btn btn-falcon-default btn-sm ms-1 ms-sm-2" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Archive" title="Archive">
                        <span class="bi bi-archive" aria-hidden="true"></span>
                    </button>
                </form>
                <form method="POST" action="{{ route('email.action', [$message['uid'], 'delete']) }}" onsubmit="return confirm('Permanently delete this email from the mailbox?');">@csrf
                    <button class="btn btn-falcon-default btn-sm ms-1 ms-sm-2" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Delete" title="Delete">
                        <span class="bi bi-trash" aria-hidden="true"></span>
                    </button>
                </form>
                <form method="POST" action="{{ route('email.action', [$message['uid'], $message['seen'] ? 'unread' : 'read']) }}">@csrf
                    <button class="btn btn-falcon-default btn-sm ms-1 ms-sm-2" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ $message['seen'] ? 'Mark as unread' : 'Mark as read' }}" title="{{ $message['seen'] ? 'Mark as unread' : 'Mark as read' }}">
                        <span class="bi {{ $message['seen'] ? 'bi-envelope' : 'bi-envelope-open' }}" aria-hidden="true"></span>
                    </button>
                </form>
                <button class="btn btn-falcon-default btn-sm ms-1 ms-sm-2 d-none d-sm-inline-block" type="button" onclick="window.print()" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print" title="Print">
                    <span class="bi bi-printer" aria-hidden="true"></span>
                </button>
            </div>
            <div class="d-flex">
                <div class="d-none d-md-block"><small>Email message</small></div>
                <div class="dropdown font-sans-serif">
                    <button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none ms-2" type="button"
                        id="email-settings" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"
                        aria-label="Mailbox options">
                        <span class="bi bi-gear" aria-hidden="true"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="email-settings">
                        <a class="dropdown-item" href="{{ route('email.mailbox-settings') }}">Configure my mailbox</a>
                        @role('admin')
                            <a class="dropdown-item" href="{{ route('email.settings') }}">Server settings</a>
                        @endrole
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('mailbox_error'))
        <div class="alert alert-danger" role="alert">{{ session('mailbox_error') }}</div>
    @endif

    <article class="card p-2">
        <div class="card-header bg-body">
            <h1 class="h4 mb-3">{{ $message['subject'] }}</h1>
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <div class="fw-semibold">{{ $message['from'] }}</div>
                    @if ($message['to'] !== '')
                        <div class="small text-body-secondary">To: {{ $message['to'] }}</div>
                    @endif
                </div>
                <time class="small text-body-secondary">{{ $message['date'] }}</time>
            </div>
        </div>
        <div class="card-body">
            @if (filled($message['body_html'] ?? null))
                <iframe class="email-message-frame" title="Email content" sandbox="" referrerpolicy="no-referrer"
                    srcdoc="{{ $message['body_html'] }}"></iframe>
                <p class="form-text mb-0 mt-2">Remote images, scripts, and external resources are blocked for privacy and safety.</p>
            @else
                <div class="email-message-body">{{ $message['body'] }}</div>
            @endif
        </div>
    </article>
</div>
<style>
    .email-message-body { white-space: pre-wrap; overflow-wrap: anywhere; }
    .email-message-frame { display: block; width: 100%; min-height: 65vh; border: 0; background: #fff; }
    @media print { .navbar, .sidebar, .card:first-child { display: none !important; } }
</style>
@endsection
