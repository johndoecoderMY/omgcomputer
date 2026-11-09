@php
    $toasts = collect([
        'success' => ['title' => session('toast_title', 'Success'), 'color' => 'success'],
        'error' => ['title' => session('toast_title', 'Error'), 'color' => 'danger'],
        'warning' => ['title' => session('toast_title', 'Warning'), 'color' => 'warning'],
        'info' => ['title' => session('toast_title', 'Information'), 'color' => 'info'],
    ])->filter(fn ($toast, $key) => session()->has($key))
        ->map(fn ($toast, $key) => [...$toast, 'message' => session($key), 'key' => $key])
        ->values();

    if ($errors->any()) {
        $toasts->push([
            'title' => session('toast_title', 'Please check your input'),
            'color' => 'danger',
            'message' => $errors->all(),
            'key' => 'validation',
        ]);
    }
@endphp

<style>
    .app-toast-container .toast {
        --falcon-toast-bg: var(--falcon-emphasis-bg);
        --falcon-toast-color: var(--falcon-body-color);
        --falcon-toast-header-bg: var(--falcon-emphasis-bg);
        --falcon-toast-header-color: var(--falcon-body-color);
    }

    .app-toast-container {
        bottom: calc(env(safe-area-inset-bottom, 0px) + .75rem);
        top: auto !important;
        max-width: min(26rem, calc(100vw - 1.5rem));
    }

    @media (max-width: 575.98px) {
        .app-toast-container {
            right: auto !important;
            left: 50%;
            width: calc(100vw - 1rem);
            max-width: 26rem;
            padding: 0 !important;
            transform: translateX(-50%);
        }

        .app-toast-container .toast {
            width: 100%;
        }
    }
</style>

@if ($toasts->isNotEmpty())
    <div class="toast-container app-toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090"
        aria-live="polite" aria-atomic="false" data-app-toast-container>
        @foreach ($toasts as $toast)
            <div class="toast border-0 shadow" role="{{ in_array($toast['key'], ['error', 'validation'], true) ? 'alert' : 'status' }}"
                aria-live="{{ in_array($toast['key'], ['error', 'validation'], true) ? 'assertive' : 'polite' }}"
                data-flash-toast data-bs-delay="{{ in_array($toast['key'], ['error', 'validation'], true) ? 8000 : 5000 }}">
                <div class="toast-header">
                    <span class="rounded-circle bg-{{ $toast['color'] }} me-2" style="width: .65rem; height: .65rem"></span>
                    <strong class="me-auto">{{ $toast['title'] }}</strong>
                    <button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" style="white-space: pre-line">
                    {{ is_array($toast['message']) ? implode("\n", $toast['message']) : $toast['message'] }}
                </div>
            </div>
        @endforeach
    </div>
@endif

@push('scripts')
    <script>
        (() => {
            const contextTitle = @json(session('toast_title'));
            const toastContainer = () => {
                let container = document.querySelector('[data-app-toast-container]');
                if (!container) {
                    container = document.createElement('div');
                    container.className = 'toast-container app-toast-container position-fixed bottom-0 end-0 p-3';
                    container.dataset.appToastContainer = '';
                    container.style.zIndex = '1090';
                    container.setAttribute('aria-live', 'polite');
                    container.setAttribute('aria-atomic', 'false');
                    document.body.append(container);
                }

                return container;
            };

            const showToast = (title, message, color, isError) => {
                const toast = document.createElement('div');
                toast.className = 'toast border-0 shadow';
                toast.setAttribute('role', isError ? 'alert' : 'status');
                toast.setAttribute('aria-live', isError ? 'assertive' : 'polite');
                toast.dataset.bsDelay = isError ? '8000' : '5000';
                toast.dataset.flashToast = '';

                const header = document.createElement('div');
                header.className = 'toast-header';
                const indicator = document.createElement('span');
                indicator.className = `rounded-circle bg-${color} me-2`;
                indicator.style.width = '.65rem';
                indicator.style.height = '.65rem';
                const heading = document.createElement('strong');
                heading.className = 'me-auto';
                heading.textContent = title;
                const close = document.createElement('button');
                close.className = 'btn-close';
                close.type = 'button';
                close.dataset.bsDismiss = 'toast';
                close.setAttribute('aria-label', 'Close');
                header.append(indicator, heading, close);

                const body = document.createElement('div');
                body.className = 'toast-body';
                body.style.whiteSpace = 'pre-line';
                body.textContent = message;
                toast.append(header, body);
                toastContainer().append(toast);
                bootstrap.Toast.getOrCreateInstance(toast).show();
            };
            window.showAppToast = (message, color = 'success', title = null) => {
                const isError = color === 'danger';
                showToast(title || contextTitle || (isError ? 'Error' : 'Success'), message, color, isError);
            };

            const normalize = (value) => value.replace(/\s+/g, ' ').trim().toLocaleLowerCase();
            const existingMessages = () => Array.from(document.querySelectorAll('[data-flash-toast] .toast-body'))
                .map((body) => normalize(body.textContent || ''))
                .filter(Boolean);

            document.querySelectorAll('[data-flash-toast]').forEach((toast) => {
                bootstrap.Toast.getOrCreateInstance(toast).show();
            });

            document.querySelectorAll('.alert-success, .alert-danger').forEach((alert) => {
                if (alert.parentElement?.closest('.alert-success, .alert-danger')) {
                    return;
                }

                const message = (alert.innerText || alert.textContent || '').trim();
                const normalizedMessage = normalize(message);
                const isError = alert.classList.contains('alert-danger');
                const isDuplicate = existingMessages().some((existing) => {
                    if (normalizedMessage.includes(existing)) {
                        return true;
                    }

                    const pieces = isError
                        ? existing.split(/\s*[•\n]\s*/).map(normalize).filter(Boolean)
                        : [existing];

                    return pieces.length > 0 && pieces.every((piece) => normalizedMessage.includes(piece));
                });

                if (!isDuplicate && message) {
                    showToast(contextTitle || (isError ? 'Error' : 'Success'), message, isError ? 'danger' : 'success', isError);
                }

                alert.remove();
            });

            const ajaxModal = document.getElementById('project-ajax-modal');
            const ajaxModalBody = ajaxModal?.querySelector('[data-project-ajax-modal-body]');
            const ajaxModalInstance = ajaxModal && window.bootstrap
                ? bootstrap.Modal.getOrCreateInstance(ajaxModal)
                : null;
            const isProjectAjaxPage = () => Boolean(document.querySelector('[data-project-ajax-root]'));
            const currentUrl = () => `${window.location.pathname}${window.location.search}`;

            const runRootScripts = (root, refreshPage = false) => {
                root.querySelectorAll('script:not([src])').forEach((script) => {
                    const executable = document.createElement('script');
                    executable.textContent = `(() => {\n${script.textContent}\n})();`;
                    script.replaceWith(executable);
                });
                if (refreshPage) {
                    window.dispatchEvent(new CustomEvent('project:content-updated'));
                }
                window.initializeProjectAjaxControls?.(root);
            };

            const showResponseToast = (parsedDocument, fallbackMessage = '') => {
                const flash = parsedDocument.querySelector('[data-flash-toast]');
                if (flash) {
                    const title = flash.querySelector('.toast-header strong')?.textContent?.trim() || 'Project update';
                    const message = flash.querySelector('.toast-body')?.textContent?.trim();
                    window.showAppToast(message || fallbackMessage || 'Changes saved.', flash.getAttribute('role') === 'alert' ? 'danger' : 'success', title);
                    return;
                }

                const alert = parsedDocument.querySelector('.alert-danger, .alert-success');
                const message = alert?.textContent?.trim();
                if (message) {
                    const isError = alert.classList.contains('alert-danger');
                    window.showAppToast(message, isError ? 'danger' : 'success', isError ? 'Project update' : 'Project update');
                } else if (fallbackMessage) {
                    window.showAppToast(fallbackMessage, 'success', 'Project update');
                }
            };

            const loadProjectModal = async (url, trigger) => {
                if (!ajaxModal || !ajaxModalBody || !ajaxModalInstance) {
                    window.location.assign(url);
                    return;
                }

                const originalLabel = trigger.textContent.trim();
                trigger.setAttribute('aria-busy', 'true');
                try {
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) {
                        throw new Error(`Could not open this form (HTTP ${response.status}).`);
                    }

                    const parsedDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const root = parsedDocument.querySelector('[data-project-ajax-root]');
                    if (!root) {
                        window.location.assign(response.url);
                        return;
                    }

                    const modalContent = root.cloneNode(true);
                    const title = modalContent.querySelector('[data-page-header] h1')?.textContent?.trim()
                        || parsedDocument.title
                        || originalLabel;
                    modalContent.querySelector('[data-page-header]')?.remove();
                    modalContent.querySelector('nav[aria-label="breadcrumb"]')?.remove();
                    ajaxModalBody.replaceChildren(modalContent);
                    ajaxModal.dataset.sourceUrl = response.url;
                    ajaxModal.querySelector('#project-ajax-modal-title').textContent = title;
                    runRootScripts(ajaxModalBody);
                    ajaxModalInstance.show();
                } catch (error) {
                    window.showAppToast(error.message || 'Could not open this form.', 'danger', 'Project form');
                } finally {
                    trigger.removeAttribute('aria-busy');
                }
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target instanceof Element
                    ? event.target.closest('a[data-project-ajax-modal]')
                    : null;
                if (!trigger || !isProjectAjaxPage() || event.defaultPrevented || event.button !== 0
                    || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const url = new URL(trigger.href, window.location.href);
                if (url.origin !== window.location.origin) {
                    return;
                }
                event.preventDefault();
                loadProjectModal(url.href, trigger);
            });

            document.addEventListener('click', (event) => {
                const cancel = event.target instanceof Element
                    ? event.target.closest('#project-ajax-modal a')
                    : null;
                if (cancel?.textContent.trim().toLocaleLowerCase() === 'cancel') {
                    event.preventDefault();
                    ajaxModalInstance?.hide();
                }
            });

            document.addEventListener('submit', async (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !isProjectAjaxPage()) {
                    return;
                }

                const formRoot = form.closest('[data-project-ajax-root]');
                const modal = form.closest('.modal');
                const projectFormModal = form.closest('#project-ajax-modal');
                const actionUrl = new URL(form.action || window.location.href, window.location.href);
                if ((!formRoot && !modal) || form.dataset.projectAjax === 'off'
                    || form.method.toLocaleLowerCase() === 'get' || form.target
                    || actionUrl.origin !== window.location.origin
                    || form.dataset.projectAjaxSubmitting === 'true') {
                    return;
                }

                event.preventDefault();
                form.dataset.projectAjaxSubmitting = 'true';
                form.setAttribute('aria-busy', 'true');
                const formData = new FormData(form);
                if (event.submitter?.name) {
                    formData.append(event.submitter.name, event.submitter.value);
                }
                const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
                const disabledState = submitButtons.map((button) => button.disabled);
                submitButtons.forEach((button) => { button.disabled = true; });

                try {
                    const response = await fetch(actionUrl.href, {
                        method: form.method.toUpperCase(),
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'text/html, application/xhtml+xml',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (response.redirected && new URL(response.url).origin !== window.location.origin) {
                        throw new Error('The server redirected this request outside the current site.');
                    }

                    const responseText = await response.text();
                    let parsedDocument = new DOMParser().parseFromString(responseText, 'text/html');
                    if (!response.ok) {
                        const message = parsedDocument.querySelector('[data-flash-toast] .toast-body')?.textContent?.trim()
                            || `Could not save your changes (HTTP ${response.status}).`;
                        window.showAppToast(message, 'danger', 'Project update');
                        return;
                    }

                    let nextUrl = new URL(response.url, window.location.href);
                    const modalSource = projectFormModal?.dataset.sourceUrl;
                    let isModalValidation = Boolean(projectFormModal && modalSource
                        && `${nextUrl.pathname}${nextUrl.search}` === `${new URL(modalSource).pathname}${new URL(modalSource).search}`);
                    let isSamePage = `${nextUrl.pathname}${nextUrl.search}` === currentUrl();
                    if (modal && !isModalValidation && isSamePage
                        && parsedDocument.querySelector('.alert-danger, [data-flash-toast][role="alert"]')) {
                        const flash = parsedDocument.querySelector('[data-flash-toast][role="alert"]');
                        const message = flash?.querySelector('.toast-body')?.textContent?.trim()
                            || parsedDocument.querySelector('.alert-danger')?.textContent?.trim()
                            || 'Please correct the highlighted form details.';
                        const modalRoot = modal.querySelector('[data-project-ajax-root]') || formRoot;
                        if (modalRoot) {
                            modalRoot.querySelector('[data-project-ajax-validation]')?.remove();
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-danger';
                            alert.setAttribute('role', 'alert');
                            alert.dataset.projectAjaxValidation = '';
                            alert.textContent = message;
                            modalRoot.prepend(alert);
                        }
                        showResponseToast(parsedDocument, message);
                        return;
                    }
                    if (!isSamePage && !isModalValidation) {
                        window.location.assign(nextUrl.href);
                        return;
                    }

                    const updatedRoot = parsedDocument.querySelector('[data-project-ajax-root]');
                    const updateMainPage = Boolean(modal && !isModalValidation && isSamePage);
                    const targetRoot = updateMainPage
                        ? document.querySelector('[data-project-ajax-root]')
                        : projectFormModal
                        ? projectFormModal.querySelector('[data-project-ajax-root]')
                        : formRoot;
                    if (!updatedRoot || !targetRoot) {
                        window.location.assign(nextUrl.href);
                        return;
                    }

                    const replacement = updatedRoot.cloneNode(true);
                    targetRoot.replaceWith(replacement);
                    if (!modal && nextUrl.href !== window.location.href) {
                        window.history.replaceState({}, '', nextUrl.href);
                    }
                    if (!modal || updateMainPage) {
                        document.title = parsedDocument.title || document.title;
                    }
                    runRootScripts(replacement, !modal || updateMainPage);
                    if (updateMainPage) {
                        bootstrap.Modal.getInstance(modal)?.hide();
                    }
                    showResponseToast(parsedDocument);
                } catch (error) {
                    window.showAppToast(error.message || 'Could not save your changes. Please try again.', 'danger', 'Project update');
                } finally {
                    form.dataset.projectAjaxSubmitting = 'false';
                    form.removeAttribute('aria-busy');
                    submitButtons.forEach((button, index) => { button.disabled = disabledState[index]; });
                }
            });
        })();
    </script>
@endpush
