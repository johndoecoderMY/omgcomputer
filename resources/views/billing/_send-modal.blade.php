@php
    $billingLanguages = \App\Models\CustomerMessageTemplate::LANGUAGES;
    $defaultLocale = old('locale', app()->getLocale());
    $defaultLocale = isset($billingLanguages[$defaultLocale]) ? $defaultLocale : 'en';
    $phone = preg_replace('/\D+/', '', (string) $sendModal['phone']);
    if (str_starts_with($phone, '0')) {
        $phone = '60'.substr($phone, 1);
    }
    $whatsappMessages = [];
    foreach ($billingLanguages as $locale => $label) {
        $replacements = $sendModal['whatsapp_replacements'];
        if (isset($sendModal['whatsapp_urls'][$locale])) {
            $replacements['url'] = $sendModal['whatsapp_urls'][$locale];
        }
        $whatsappMessages[$locale] = \Illuminate\Support\Facades\Lang::get(
            $sendModal['whatsapp_key'],
            $replacements,
            $locale,
        );
    }
@endphp
<div class="modal fade" id="{{ $sendModal['id'] }}" tabindex="-1" aria-labelledby="{{ $sendModal['id'] }}-title" aria-hidden="true"
    data-billing-send-modal
    data-email-preview-url="{{ $sendModal['email_preview_url'] }}"
    data-pdf-preview-url="{{ $sendModal['pdf_preview_url'] }}"
    data-pdf-download-url="{{ $sendModal['pdf_preview_url'] }}?download=1"
    data-whatsapp-number="{{ $phone }}"
    @foreach ($whatsappMessages as $locale => $message)
        data-whatsapp-message-{{ strtolower(str_replace('_', '-', $locale)) }}="{{ $message }}"
    @endforeach
    @foreach (($sendModal['whatsapp_urls'] ?? []) as $locale => $url)
        data-whatsapp-pdf-url-{{ strtolower(str_replace('_', '-', $locale)) }}="{{ $url }}"
    @endforeach>
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ $sendModal['action'] }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="{{ $sendModal['id'] }}-title">{{ $sendModal['title'] }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @once
                    @push('scripts')
                        <script>
                            document.querySelectorAll('[data-billing-send-modal]').forEach((modal) => {
                                const localeSelect = modal.querySelector('[data-billing-send-locale]');
                                const emailPreview = modal.querySelector('[data-billing-email-preview]');
                                const pdfPreview = modal.querySelector('[data-billing-pdf-preview]');
                                const pdfDownload = modal.querySelector('[data-billing-pdf-download]');
                                const whatsappPreview = modal.querySelector('[data-billing-whatsapp-preview]');
                                const whatsappLink = modal.querySelector('[data-billing-whatsapp-link]');
                                const whatsappFileShare = modal.querySelector('[data-billing-whatsapp-file-share]');
                                if (!localeSelect) return;

                                const updatePreview = () => {
                                    const locale = localeSelect.value;
                                    const setLocale = (link, baseUrl) => {
                                        if (!link || !baseUrl) return;
                                        const url = new URL(baseUrl, window.location.href);
                                        url.searchParams.set('locale', locale);
                                        link.href = url.toString();
                                    };
                                    setLocale(emailPreview, modal.dataset.emailPreviewUrl);
                                    setLocale(pdfPreview, modal.dataset.pdfPreviewUrl);
                                    setLocale(pdfDownload, modal.dataset.pdfDownloadUrl);

                                    const message = modal.getAttribute(`data-whatsapp-message-${locale.replace('_', '-').toLowerCase()}`);
                                    if (message === null) return;
                                    if (whatsappPreview) whatsappPreview.value = message;
                                    if (whatsappLink) {
                                        whatsappLink.href = `https://wa.me/${modal.dataset.whatsappNumber}?text=${encodeURIComponent(message)}`;
                                    }
                                };

                                localeSelect.addEventListener('change', updatePreview);
                                updatePreview();

                                whatsappLink?.addEventListener('click', () => {
                                    const statusUrl = whatsappLink.dataset.markSentUrl;
                                    if (!statusUrl) return;

                                    fetch(statusUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                            'X-CSRF-TOKEN': whatsappLink.dataset.csrf,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        body: new URLSearchParams({ status: 'sent', delivery: 'whatsapp' }),
                                    });
                                });

                                whatsappFileShare?.addEventListener('click', async () => {
                                    const localeKey = localeSelect.value.replace('_', '-').toLowerCase();
                                    const pdfUrl = modal.getAttribute(`data-whatsapp-pdf-url-${localeKey}`);
                                    const message = modal.getAttribute(`data-whatsapp-message-${localeKey}`);
                                    if (!pdfUrl || !message) {
                                        whatsappFileShare.textContent = 'PDF sharing is unavailable. Download the PDF and attach it manually.';
                                        return;
                                    }

                                    whatsappFileShare.disabled = true;
                                    try {
                                        const statusUrl = whatsappFileShare.dataset.markSentUrl;
                                        if (statusUrl) {
                                            const statusResponse = await fetch(statusUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/x-www-form-urlencoded',
                                                    'X-CSRF-TOKEN': whatsappFileShare.dataset.csrf,
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                },
                                                body: new URLSearchParams({ status: 'sent', delivery: 'whatsapp' }),
                                            });
                                            if (!statusResponse.ok) throw new Error(`Quotation status update failed with status ${statusResponse.status}`);
                                        }
                                        const response = await fetch(pdfUrl, { credentials: 'same-origin' });
                                        if (!response.ok) throw new Error(`PDF request failed with status ${response.status}`);
                                        const file = new File([await response.blob()], whatsappFileShare.dataset.filename, {
                                            type: 'application/pdf',
                                        });
                                        let canShareFile = false;
                                        try {
                                            canShareFile = Boolean(
                                                navigator.share && navigator.canShare && navigator.canShare({ files: [file] }),
                                            );
                                        } catch {
                                            canShareFile = false;
                                        }
                                        if (!canShareFile) {
                                            pdfDownload?.click();
                                            whatsappFileShare.textContent = 'PDF downloaded. Attach it to the WhatsApp draft.';
                                            return;
                                        }

                                        await navigator.share({
                                            files: [file],
                                            title: whatsappFileShare.dataset.title,
                                            text: message,
                                        });
                                    } catch (error) {
                                        if (error.name !== 'AbortError') {
                                            console.error('Could not share billing PDF.', error);
                                            whatsappFileShare.textContent = 'Could not share PDF. Download it and attach it manually.';
                                        }
                                    } finally {
                                        whatsappFileShare.disabled = false;
                                    }
                                });
                            });
                        </script>
                    @endpush
                @endonce
                <div class="modal-body">
                    <p class="text-body-secondary">{{ $sendModal['description'] }}</p>
                    <div class="border rounded p-3 mb-3">
                        <label class="form-label fw-semibold" for="{{ $sendModal['id'] }}-email">
                            <span class="bi bi-envelope me-2" aria-hidden="true"></span>Email PDF
                        </label>
                        <input class="form-control mb-3" id="{{ $sendModal['id'] }}-email" type="email"
                            name="email" value="{{ old('email', $sendModal['email']) }}" required>
                        <label class="form-label" for="{{ $sendModal['id'] }}-locale">Email and PDF language</label>
                        <select class="form-select" id="{{ $sendModal['id'] }}-locale" name="locale" data-billing-send-locale>
                            @foreach ($billingLanguages as $locale => $label)
                                <option value="{{ $locale }}" @selected($defaultLocale === $locale)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a class="btn btn-sm btn-outline-primary" href="{{ $sendModal['email_preview_url'] }}?locale={{ $defaultLocale }}"
                                target="_blank" rel="noopener" data-billing-email-preview>
                                <span class="bi bi-eye me-1" aria-hidden="true"></span>Preview email
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ $sendModal['pdf_preview_url'] }}?locale={{ $defaultLocale }}"
                                target="_blank" rel="noopener" data-billing-pdf-preview>
                                <span class="bi bi-filetype-pdf me-1" aria-hidden="true"></span>Preview PDF
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ $sendModal['pdf_preview_url'] }}?download=1&locale={{ $defaultLocale }}"
                                download data-billing-pdf-download>
                                <span class="bi bi-download me-1" aria-hidden="true"></span>Download PDF to attach
                            </a>
                        </div>
                    </div>

                    <div class="border rounded p-3">
                        <div class="fw-semibold mb-1"><span class="bi bi-whatsapp me-2" aria-hidden="true"></span>WhatsApp preview</div>
                        <label class="visually-hidden" for="{{ $sendModal['id'] }}-whatsapp-message">WhatsApp message preview</label>
                        <textarea class="form-control mb-3" id="{{ $sendModal['id'] }}-whatsapp-message"
                            rows="4" readonly data-billing-whatsapp-preview>{{ $whatsappMessages[$defaultLocale] }}</textarea>
                        @if ($phone !== '')
                            <button class="btn btn-outline-success w-100 mb-2" type="button"
                                data-billing-whatsapp-file-share
                                data-filename="{{ strtolower($sendModal['filename'] ?? 'billing-document') }}.pdf"
                                data-title="{{ $sendModal['title'] }}"
                                @if (! empty($sendModal['mark_sent_url']))
                                    data-mark-sent-url="{{ $sendModal['mark_sent_url'] }}"
                                    data-csrf="{{ csrf_token() }}"
                                @endif>
                                <span class="bi bi-paperclip me-2" aria-hidden="true"></span>Share PDF attachment
                            </button>
                            <div class="form-text mb-3">On supported devices, choose WhatsApp from the share sheet. Otherwise, download the PDF and attach it to the draft.</div>
                            <a class="btn btn-success w-100" href="https://wa.me/{{ $phone }}?text={{ rawurlencode($whatsappMessages[$defaultLocale]) }}"
                                target="_blank" rel="noopener" data-billing-whatsapp-link
                                @if (! empty($sendModal['mark_sent_url']))
                                    data-mark-sent-url="{{ $sendModal['mark_sent_url'] }}"
                                    data-csrf="{{ csrf_token() }}"
                                @endif>
                                Open WhatsApp draft
                            </a>
                        @else
                            <div class="alert alert-secondary mb-0">Add a customer phone number to create a WhatsApp draft.</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <span class="bi bi-send me-2" aria-hidden="true"></span>{{ $sendModal['submit_label'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
