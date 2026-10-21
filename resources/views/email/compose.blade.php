@extends('layouts.app')

@section('title', 'Compose Email')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Email" title="Compose" description="Write a message from your cPanel mailbox."
        :back-url="route('email.inbox')" back-label="Email inbox" />

    @if (session('compose_error'))
        <div class="alert alert-danger" role="alert">{{ session('compose_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @unless ($mailbox)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
            <span>Connect your cPanel mailbox before sending email.</span>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('email.mailbox-settings') }}">Configure mailbox</a>
        </div>
    @endunless

    <form class="card" method="POST" action="{{ route('email.send') }}" enctype="multipart/form-data">
        @csrf
        <div class="card-header bg-body-tertiary">
            <h2 class="h5 mb-0">New message</h2>
            <div class="small text-body-secondary mt-1">
                From: {{ $mailbox?->email ?? 'Mailbox not configured' }}
            </div>
        </div>
        <div class="card-body p-0">
            <div class="email-recipient-row border-bottom border-200 d-flex align-items-start px-x1 py-2">
                <label class="email-recipient-label pt-2" for="email-recipient-input">To</label>
                <div class="email-recipient-field flex-grow-1 d-flex flex-wrap align-items-center gap-1">
                    @php($oldRecipients = old('recipients', []))
                    @if (! is_array($oldRecipients))
                        @php($oldRecipients = [])
                    @endif
                    <div class="email-recipient-chips d-flex flex-wrap align-items-center gap-1" id="email-recipient-chips">
                        @foreach ($oldRecipients as $recipient)
                            @if (is_string($recipient) && $recipient !== '')
                                <span class="badge rounded-pill bg-body-secondary text-body email-recipient-chip" data-recipient="{{ $recipient }}">
                                    <span>{{ $recipient }}</span>
                                    <button class="email-recipient-remove" type="button" aria-label="Remove {{ $recipient }}">&times;</button>
                                    <input type="hidden" name="recipients[]" value="{{ $recipient }}">
                                </span>
                            @endif
                        @endforeach
                    </div>
                    <input class="form-control border-0 rounded-0 outline-none p-1 flex-grow-1 email-recipient-input" id="email-recipient-input"
                        type="text" placeholder="Add recipients" autocomplete="email" aria-describedby="email-recipient-help">
                </div>
            </div>
            <div class="visually-hidden" id="email-recipient-help">Type an email address and press Enter or comma to add another recipient.</div>
            <div class="border-bottom border-200">
                <label class="visually-hidden" for="email-subject">Subject</label>
                <input class="form-control border-0 rounded-0 outline-none px-x1" id="email-subject" name="subject"
                    type="text" value="{{ old('subject') }}" placeholder="Subject" maxlength="255" required>
            </div>
            <div class="email-compose-textarea">
                <label class="visually-hidden" for="email-body">Message</label>
                <textarea class="form-control border-0 rounded-0 px-x1 py-3" id="email-body" name="body"
                    rows="12" maxlength="100000" placeholder="Write your message…" required>{{ old('body') }}</textarea>
            </div>
            <div class="bg-body-tertiary px-x1 py-3">
                <div class="small text-body-secondary" id="email-attachment-list" aria-live="polite">No attachments selected</div>
            </div>
        </div>
        <div class="card-footer border-top border-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-primary btn-sm px-4" type="submit">
                    <span class="fas fa-paper-plane me-2" aria-hidden="true"></span>Send
                </button>
                <input class="visually-hidden" id="email-attachments" name="attachments[]" type="file" multiple
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip">
                <label class="btn btn-tertiary btn-sm mb-0" for="email-attachments">
                    <span class="fas fa-paperclip me-1" aria-hidden="true"></span>Attach files
                </label>
                <span class="small text-body-secondary">Up to 5 files, 10 MB each</span>
            </div>
            <a class="btn btn-tertiary btn-sm" href="{{ route('email.inbox') }}" aria-label="Discard message" title="Discard message">
                <span class="fas fa-trash-alt" aria-hidden="true"></span>
            </a>
        </div>
    </form>
</div>

<script>
    (() => {
        const form = document.querySelector('form[action="{{ route('email.send') }}"]');
        const recipientInput = document.getElementById('email-recipient-input');
        const recipientChips = document.getElementById('email-recipient-chips');
        const input = document.getElementById('email-attachments');
        const list = document.getElementById('email-attachment-list');

        const recipients = () => [...recipientChips.querySelectorAll('input[name="recipients[]"]')]
            .map((recipient) => recipient.value.trim().toLowerCase());

        const addRecipient = (rawValue) => {
            const address = rawValue.trim().replace(/[;,]+$/, '').trim();
            if (!address) return true;
            if (!/^[^\s@,;]+@[^\s@,;]+\.[^\s@,;]+$/.test(address)) {
                recipientInput.setCustomValidity('Enter a valid email address.');
                recipientInput.reportValidity();
                return false;
            }
            if (recipients().includes(address.toLowerCase())) {
                recipientInput.setCustomValidity('This recipient has already been added.');
                recipientInput.reportValidity();
                return false;
            }
            if (recipientChips.querySelectorAll('input[name="recipients[]"]').length >= 50) {
                recipientInput.setCustomValidity('You can add up to 50 recipients.');
                recipientInput.reportValidity();
                return false;
            }

            recipientInput.setCustomValidity('');
            const chip = document.createElement('span');
            chip.className = 'badge rounded-pill bg-body-secondary text-body email-recipient-chip';
            const label = document.createElement('span');
            label.textContent = address;
            const remove = document.createElement('button');
            remove.className = 'email-recipient-remove';
            remove.type = 'button';
            remove.setAttribute('aria-label', `Remove ${address}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => chip.remove());
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'recipients[]';
            hidden.value = address;
            chip.append(label, remove, hidden);
            recipientChips.append(chip);
            recipientInput.value = '';
            return true;
        };

        recipientChips.querySelectorAll('.email-recipient-chip').forEach((chip) => {
            chip.querySelector('.email-recipient-remove').addEventListener('click', () => chip.remove());
        });
        recipientInput.addEventListener('input', () => recipientInput.setCustomValidity(''));
        recipientInput.addEventListener('paste', (event) => {
            const pasted = event.clipboardData.getData('text');
            if (!/[,;\r\n]/.test(pasted)) return;
            event.preventDefault();
            pasted.split(/[,;\r\n]+/).forEach((address) => {
                if (address.trim() !== '') addRecipient(address);
            });
        });
        recipientInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
                event.preventDefault();
                addRecipient(recipientInput.value);
            } else if (event.key === 'Backspace' && recipientInput.value === '') {
                recipientChips.lastElementChild?.remove();
            }
        });
        recipientInput.addEventListener('blur', () => {
            if (recipientInput.value.trim() !== '') addRecipient(recipientInput.value);
        });
        form.addEventListener('submit', (event) => {
            if (recipientInput.value.trim() !== '' && !addRecipient(recipientInput.value)) {
                event.preventDefault();
                recipientInput.focus();
                return;
            }
            if (recipients().length === 0) {
                event.preventDefault();
                recipientInput.setCustomValidity('Add at least one recipient.');
                recipientInput.reportValidity();
                recipientInput.focus();
            }
        });

        input.addEventListener('change', () => {
            const files = [...input.files].map((file) => `${file.name} (${(file.size / 1024 / 1024).toFixed(1)} MB)`);
            list.textContent = files.length ? files.join(' · ') : 'No attachments selected';
        });
    })();
</script>
<style>
    .email-recipient-label { width: 3rem; flex-shrink: 0; }
    .email-recipient-field { min-width: 0; }
    .email-recipient-input { min-width: 12rem; }
    .email-recipient-chip { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; padding: .35rem .5rem; }
    .email-recipient-remove { border: 0; padding: 0; color: inherit; background: transparent; font-size: 1rem; line-height: 1; }
</style>
@endsection
