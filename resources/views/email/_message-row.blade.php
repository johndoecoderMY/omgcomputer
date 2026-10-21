<div class="row border-bottom border-200 hover-actions-trigger hover-shadow py-2 px-1 mx-0 email-row {{ $message['seen'] ? 'email-row-read' : 'email-row-unread' }}"
    data-email-row data-uid="{{ $message['uid'] }}" data-seen="{{ $message['seen'] ? '1' : '0' }}"
    data-flagged="{{ ($message['flagged'] ?? false) ? '1' : '0' }}" data-to="{{ strtolower($message['to'] ?? '') }}">
    <div class="btn-group btn-group-sm z-2 hover-actions end-0 me-3 email-row-actions">
        <form method="POST" action="{{ route('email.action', [$message['uid'], 'archive']) }}">@csrf
            <button class="btn btn-tertiary hover-bg-200" type="submit" aria-label="Archive" title="Archive"><span class="fas fa-archive" aria-hidden="true"></span></button>
        </form>
        <form method="POST" action="{{ route('email.action', [$message['uid'], 'delete']) }}" onsubmit="return confirm('Permanently delete this email from the mailbox?');">@csrf
            <button class="btn btn-tertiary hover-bg-200" type="submit" aria-label="Delete" title="Delete"><span class="fas fa-trash-alt" aria-hidden="true"></span></button>
        </form>
        <form method="POST" action="{{ route('email.action', [$message['uid'], $message['seen'] ? 'unread' : 'read']) }}">@csrf
            <button class="btn btn-tertiary hover-bg-200" type="submit" aria-label="{{ $message['seen'] ? 'Mark as unread' : 'Mark as read' }}" title="{{ $message['seen'] ? 'Mark as unread' : 'Mark as read' }}">
                <span class="fas {{ $message['seen'] ? 'fa-envelope' : 'fa-envelope-open' }}" aria-hidden="true"></span>
            </button>
        </form>
    </div>
    <div class="col-auto d-none d-sm-block">
        <div class="d-flex bg-body-tertiary">
            <div class="form-check mb-0 fs-9">
                <input class="form-check-input email-select" type="checkbox" value="{{ $message['uid'] }}" aria-label="Select email: {{ $message['subject'] }}">
            </div>
            <form method="POST" action="{{ route('email.action', [$message['uid'], ($message['flagged'] ?? false) ? 'unflag' : 'flag']) }}" class="ms-1">@csrf
                <button class="btn btn-link p-0 border-0 text-decoration-none" type="submit"
                    aria-label="{{ ($message['flagged'] ?? false) ? 'Remove flag' : 'Flag email' }}" title="{{ ($message['flagged'] ?? false) ? 'Remove flag' : 'Flag email' }}">
                    <span class="fas fa-star {{ ($message['flagged'] ?? false) ? 'text-warning' : 'text-300' }}" aria-hidden="true"></span>
                </button>
            </form>
        </div>
    </div>
    <div class="col col-md-9 col-xxl-10">
        <div class="row">
            <div class="col-md-4 col-xl-3 col-xxl-2 ps-md-0 mb-1 mb-md-0">
                <div class="d-flex position-relative align-items-center">
                    <span class="avatar avatar-s bg-body-secondary text-primary fw-bold d-inline-flex align-items-center justify-content-center" aria-hidden="true">{{ str($message['from'] ?: '?')->trim()->substr(0, 1)->upper() }}</span>
                    <a class="flex-1 ms-2 email-sender inbox-link stretched-link {{ $message['seen'] ? '' : 'fw-bold' }}"
                        href="{{ route('email.show', $message['uid']) }}">{{ $message['from'] ?: 'Unknown sender' }}</a>
                </div>
            </div>
            <div class="col">
                <a class="d-block inbox-link email-subject {{ $message['seen'] ? '' : 'fw-bold' }}" href="{{ route('email.show', $message['uid']) }}">
                    {{ $message['subject'] ?: '(No subject)' }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-auto ms-auto d-flex flex-column justify-content-between {{ $message['seen'] ? 'text-body-secondary' : 'fw-bold' }}">
        <time class="small">{{ $message['date'] }}</time>
    </div>
</div>
