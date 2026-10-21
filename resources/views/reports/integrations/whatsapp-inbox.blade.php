@extends('layouts.app')

@section('title', 'WhatsApp Inbox')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="WhatsApp Inbox"
        description="Inbound messages delivered by the WhatsApp Cloud API webhook."
        :back-url="route('reports.integrations.whatsapp')" back-label="WhatsApp setup">
        <x-slot:actions>
            <span class="small text-body-secondary" id="whatsapp-inbox-status" role="status">Auto-refresh every 15 seconds</span>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="list-group list-group-flush" id="whatsapp-inbound-messages" data-poll-url="{{ route('reports.integrations.whatsapp.inbox.poll') }}">
            @forelse ($messages as $message)
                <article class="list-group-item p-3 d-flex gap-3 whatsapp-message" data-message-id="{{ $message->id }}">
                    <span class="avatar avatar-s bg-success-subtle text-success d-flex align-items-center justify-content-center" aria-hidden="true">
                        <span class="fab fa-whatsapp"></span>
                    </span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="fw-semibold">{{ $message->contact_name ?: $message->wa_id }}</div>
                            <time class="small text-body-secondary" datetime="{{ $message->received_at->toIso8601String() }}">{{ $message->received_at->format('M j, Y H:i') }}</time>
                        </div>
                        @if ($message->contact_name)
                            <div class="small text-body-secondary">{{ $message->wa_id }}</div>
                        @endif
                        <div class="mt-2 text-break">{{ $message->message_body ?: '['.ucfirst($message->message_type).' message]' }}</div>
                    </div>
                </article>
            @empty
                <div class="text-center text-body-secondary py-5" id="whatsapp-inbox-empty">
                    <span class="fab fa-whatsapp fs-2 d-block mb-2" aria-hidden="true"></span>
                    No inbound WhatsApp messages yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    (() => {
        const list = document.getElementById('whatsapp-inbound-messages');
        const status = document.getElementById('whatsapp-inbox-status');
        const pollUrl = list.dataset.pollUrl;
        let polling = false;

        const renderMessage = (message) => {
            const item = document.createElement('article');
            item.className = 'list-group-item p-3 d-flex gap-3 whatsapp-message';
            item.dataset.messageId = message.id;

            const icon = document.createElement('span');
            icon.className = 'avatar avatar-s bg-success-subtle text-success d-flex align-items-center justify-content-center';
            icon.setAttribute('aria-hidden', 'true');
            const whatsappIcon = document.createElement('span');
            whatsappIcon.className = 'fab fa-whatsapp';
            icon.append(whatsappIcon);

            const content = document.createElement('div');
            content.className = 'flex-grow-1 min-w-0';
            const header = document.createElement('div');
            header.className = 'd-flex flex-wrap justify-content-between gap-2';
            const sender = document.createElement('div');
            sender.className = 'fw-semibold';
            sender.textContent = message.contact_name || message.wa_id;
            const date = document.createElement('time');
            date.className = 'small text-body-secondary';
            date.dateTime = message.received_at;
            date.textContent = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(message.received_at));
            header.append(sender, date);
            content.append(header);

            if (message.contact_name) {
                const number = document.createElement('div');
                number.className = 'small text-body-secondary';
                number.textContent = message.wa_id;
                content.append(number);
            }

            const body = document.createElement('div');
            body.className = 'mt-2 text-break';
            body.textContent = message.message_body || `[${message.message_type} message]`;
            content.append(body);
            item.append(icon, content);
            return item;
        };

        const refresh = async () => {
            if (polling || document.hidden) return;
            polling = true;
            try {
                const response = await fetch(pollUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error('Could not refresh WhatsApp messages.');
                const payload = await response.json();
                const empty = list.querySelector('#whatsapp-inbox-empty');
                const existing = new Map([...list.querySelectorAll('[data-message-id]')].map((item) => [item.dataset.messageId, item]));
                const visibleIds = new Set();
                (payload.messages || []).forEach((message) => {
                    const id = String(message.id);
                    visibleIds.add(id);
                    const item = existing.get(id) || renderMessage(message);
                    list.append(item);
                });
                existing.forEach((item, id) => {
                    if (!visibleIds.has(id)) item.remove();
                });
                if ((payload.messages || []).length > 0) empty?.remove();
                else if (!empty) {
                    const emptyState = document.createElement('div');
                    emptyState.id = 'whatsapp-inbox-empty';
                    emptyState.className = 'text-center text-body-secondary py-5';
                    emptyState.textContent = 'No inbound WhatsApp messages yet.';
                    list.append(emptyState);
                }
                status.textContent = `Updated ${new Date(payload.checked_at).toLocaleTimeString()}`;
            } catch {
                status.textContent = 'Refresh failed; retrying soon';
            } finally {
                polling = false;
            }
        };

        window.setInterval(refresh, 15000);
    })();
</script>
@endsection
