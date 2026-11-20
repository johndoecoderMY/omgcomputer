<script>
    (() => {
        const body = document.body;
        if (body.dataset.staffPwa !== 'true' || !body.dataset.notificationsUser) return;

        const databaseName = 'omg-staff-offline-drafts';
        const storeName = 'drafts';
        const draftLifetime = 7 * 24 * 60 * 60 * 1000;
        const maxFileBytes = 80 * 1024 * 1024;
        const pendingSubmissionKey = 'omg-staff-offline-draft-pending';
        const initializedForms = new WeakSet();
        const restoringForms = new WeakSet();
        const statuses = new WeakMap();
        const saveTimers = new WeakMap();
        const saveQueues = new WeakMap();
        let databasePromise;

        const getDatabase = () => {
            if (!databasePromise) {
                databasePromise = new Promise((resolve, reject) => {
                    if (!window.indexedDB) {
                        reject(new Error('IndexedDB is not available in this browser.'));
                        return;
                    }

                    const request = window.indexedDB.open(databaseName, 1);
                    request.onupgradeneeded = () => {
                        request.result.createObjectStore(storeName, { keyPath: 'key' });
                    };
                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error || new Error('Could not open offline draft storage.'));
                    request.onblocked = () => reject(new Error('Offline draft storage is blocked by another tab.'));
                });
            }

            return databasePromise;
        };

        const getDraft = async (key) => {
            const database = await getDatabase();
            return new Promise((resolve, reject) => {
                const transaction = database.transaction(storeName, 'readonly');
                const request = transaction.objectStore(storeName).get(key);
                request.onsuccess = () => resolve(request.result || null);
                request.onerror = () => reject(request.error || new Error('Could not read the offline draft.'));
                transaction.onabort = () => reject(transaction.error || new Error('Could not read the offline draft.'));
            });
        };

        const putDraft = async (draft) => {
            const database = await getDatabase();
            return new Promise((resolve, reject) => {
                const transaction = database.transaction(storeName, 'readwrite');
                transaction.objectStore(storeName).put(draft);
                transaction.oncomplete = resolve;
                transaction.onerror = () => reject(transaction.error || new Error('Could not save the offline draft.'));
                transaction.onabort = () => reject(transaction.error || new Error('Could not save the offline draft.'));
            });
        };

        const deleteDraftByKey = async (key) => {
            const database = await getDatabase();
            return new Promise((resolve, reject) => {
                const transaction = database.transaction(storeName, 'readwrite');
                transaction.objectStore(storeName).delete(key);
                transaction.oncomplete = resolve;
                transaction.onerror = () => reject(transaction.error || new Error('Could not clear the offline draft.'));
                transaction.onabort = () => reject(transaction.error || new Error('Could not clear the offline draft.'));
            });
        };

        const removeExpiredDrafts = async () => {
            const database = await getDatabase();
            return new Promise((resolve, reject) => {
                const transaction = database.transaction(storeName, 'readwrite');
                const store = transaction.objectStore(storeName);
                const request = store.getAll();
                request.onsuccess = () => {
                    const now = Date.now();
                    request.result.forEach((draft) => {
                        if (draft.expiresAt <= now) store.delete(draft.key);
                    });
                };
                transaction.oncomplete = resolve;
                transaction.onerror = () => reject(transaction.error || new Error('Could not clean up expired offline drafts.'));
                transaction.onabort = () => reject(transaction.error || new Error('Could not clean up expired offline drafts.'));
            });
        };

        const getDraftKey = (form) => {
            const action = new URL(form.action || window.location.href, window.location.href);
            return [
                body.dataset.notificationsUser,
                `${window.location.pathname}${window.location.search}`,
                form.dataset.offlineDraft,
                form.method.toUpperCase(),
                `${action.pathname}${action.search}`,
            ].join('|');
        };

        const getStatus = (form) => {
            let status = statuses.get(form);
            if (status) return status;

            status = document.createElement('div');
            status.className = 'alert alert-secondary d-flex align-items-center justify-content-between gap-2 py-2 small d-none';
            status.setAttribute('role', 'status');
            const message = document.createElement('span');
            message.dataset.offlineDraftMessage = '';
            const clearButton = document.createElement('button');
            clearButton.className = 'btn btn-sm btn-outline-secondary flex-shrink-0 d-none';
            clearButton.type = 'button';
            clearButton.textContent = 'Clear saved copy';
            clearButton.dataset.offlineDraftClear = '';
            clearButton.addEventListener('click', (event) => {
                event.stopPropagation();
                clearDraft(form).then(() => {
                    setStatus(form, 'Saved copy cleared. The current form values are unchanged.', 'secondary');
                }).catch((error) => {
                    console.error('Could not clear the offline draft.', error);
                    setStatus(form, 'Could not clear the saved copy from this browser.', 'danger', true);
                });
            });
            status.append(message, clearButton);

            const footer = Array.from(form.children).find((child) =>
                child.matches('.modal-footer, .card-footer, .d-flex.justify-content-end'));
            if (footer) {
                form.insertBefore(status, footer);
            } else {
                form.append(status);
            }

            status._message = message;
            status._clearButton = clearButton;
            statuses.set(form, status);
            return status;
        };

        const setStatus = (form, message, type = 'secondary', showClear = false) => {
            const status = getStatus(form);
            status.className = `alert alert-${type} d-flex align-items-center justify-content-between gap-2 py-2 small`;
            status._message.textContent = message;
            status._clearButton.classList.toggle('d-none', !showClear);
        };

        const hideStatus = (form) => {
            getStatus(form).classList.add('d-none');
        };

        const isSupportedControl = (control) => (
            control instanceof HTMLInputElement
            || control instanceof HTMLSelectElement
            || control instanceof HTMLTextAreaElement
        );

        const isExcludedControl = (control) => {
            const type = (control.type || '').toLowerCase();
            return control.disabled
                || ['button', 'submit', 'reset', 'image', 'password'].includes(type)
                || ['_token', '_method'].includes(control.name);
        };

        const captureDraft = (form) => {
            const fields = [];
            const files = [];
            const fieldOrdinals = new Map();
            const fileOrdinals = new Map();
            let fileBytes = 0;

            Array.from(form.elements).forEach((control) => {
                const name = control.name || control.dataset?.offlineDraftField;
                if (!isSupportedControl(control) || !name || isExcludedControl(control)) return;

                if (control instanceof HTMLInputElement && control.type === 'file') {
                    const ordinal = fileOrdinals.get(name) || 0;
                    fileOrdinals.set(name, ordinal + 1);
                    const items = Array.from(control.files || []).map((file) => {
                        fileBytes += file.size;
                        return {
                            name: file.name,
                            type: file.type,
                            lastModified: file.lastModified,
                            blob: file.slice(0, file.size, file.type),
                        };
                    });
                    files.push({ name, ordinal, items });
                    return;
                }

                const ordinal = fieldOrdinals.get(name) || 0;
                fieldOrdinals.set(name, ordinal + 1);
                const field = {
                    name,
                    ordinal,
                    type: (control.type || control.tagName).toLowerCase(),
                    value: control instanceof HTMLSelectElement && control.multiple
                        ? Array.from(control.selectedOptions, (option) => option.value)
                        : control.value,
                };
                if (control instanceof HTMLSelectElement && control.dataset.offlineDraftField) {
                    const row = control.closest('.billing-line');
                    field.optionLabel = row?.querySelector('.line-description')?.value || '';
                    field.optionPrice = row?.querySelector('.line-price')?.value || '';
                }
                if (control instanceof HTMLInputElement && ['checkbox', 'radio'].includes(control.type)) {
                    field.checked = control.checked;
                }
                fields.push(field);
            });

            if (fileBytes > maxFileBytes) {
                throw new Error('Selected attachments exceed the 80 MB local backup limit.');
            }

            const existingCreatedAt = Number(form.dataset.offlineDraftCreatedAt);
            return {
                key: getDraftKey(form),
                userId: body.dataset.notificationsUser,
                fields,
                files,
                dynamicCounts: {
                    billingLines: form.querySelectorAll('#lines .billing-line').length,
                    projectRequirements: form.querySelectorAll('#project-requirements [data-requirement-row]').length,
                },
                createdAt: existingCreatedAt || Date.now(),
                updatedAt: Date.now(),
                expiresAt: Date.now() + draftLifetime,
            };
        };

        const saveDraft = (form) => {
            const timer = saveTimers.get(form);
            if (timer) {
                window.clearTimeout(timer);
                saveTimers.delete(form);
            }

            let draft;
            try {
                draft = captureDraft(form);
            } catch (error) {
                return Promise.reject(error);
            }

            const previousSave = saveQueues.get(form) || Promise.resolve();
            const nextSave = previousSave.catch(() => {}).then(() => putDraft(draft));
            saveQueues.set(form, nextSave);
            return nextSave.then(() => {
                form.dataset.offlineDraftCreatedAt = String(draft.createdAt);
                form.dataset.offlineDraftSaved = 'true';
                return draft;
            });
        };

        const clearDraft = async (form) => {
            const timer = saveTimers.get(form);
            if (timer) {
                window.clearTimeout(timer);
                saveTimers.delete(form);
            }
            const previousSave = saveQueues.get(form);
            if (previousSave) await previousSave.catch(() => {});
            await deleteDraftByKey(getDraftKey(form));
            delete form.dataset.offlineDraftCreatedAt;
            delete form.dataset.offlineDraftSaved;
            hideStatus(form);
        };

        const saveAndShowStatus = (form) => {
            saveDraft(form).then((draft) => {
                const savedAt = new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' })
                    .format(new Date(draft.updatedAt));
                setStatus(
                    form,
                    navigator.onLine
                        ? `Draft saved on this device at ${savedAt}. Local copies expire after 7 days.`
                        : 'You are offline. Your draft is saved on this device; reconnect, then submit it manually.',
                    navigator.onLine ? 'success' : 'warning',
                    true,
                );
            }).catch((error) => {
                console.error('Could not save the offline draft.', error);
                setStatus(form, 'Could not save a local backup. Keep this page open while offline.', 'danger', true);
            });
        };

        const scheduleSave = (form) => {
            if (restoringForms.has(form)) return;
            const currentTimer = saveTimers.get(form);
            if (currentTimer) window.clearTimeout(currentTimer);
            saveTimers.set(form, window.setTimeout(() => {
                saveTimers.delete(form);
                saveAndShowStatus(form);
            }, 600));
        };

        const indexedFieldControls = (form, name) => Array.from(form.elements).filter((control) =>
            isSupportedControl(control) && (control.name || control.dataset?.offlineDraftField) === name
            && !isExcludedControl(control)
            && !(control instanceof HTMLInputElement && control.type === 'file'));

        const applyFields = (form, fields) => {
            fields.forEach((field) => {
                const control = indexedFieldControls(form, field.name)[field.ordinal];
                if (!control) return;
                if (control instanceof HTMLInputElement && ['checkbox', 'radio'].includes(control.type)) {
                    control.checked = Boolean(field.checked);
                } else if (control instanceof HTMLSelectElement && control.multiple) {
                    const selectedValues = new Set(Array.isArray(field.value) ? field.value : []);
                    Array.from(control.options).forEach((option) => {
                        option.selected = selectedValues.has(option.value);
                    });
                } else {
                    const value = Array.isArray(field.value) ? field.value[0] || '' : field.value;
                    if (control instanceof HTMLSelectElement && value
                        && !Array.from(control.options).some((option) => option.value === value)) {
                        const option = new Option(
                            field.optionLabel ? `${field.optionLabel} (saved draft)` : value,
                            value,
                            true,
                            true,
                        );
                        option.dataset.name = field.optionLabel || '';
                        option.dataset.price = field.optionPrice || '';
                        control.add(option);
                    }
                    control.value = value;
                }
            });
        };

        const restoreFiles = (form, files) => {
            files.forEach((fileRecord) => {
                const inputs = Array.from(form.elements).filter((control) =>
                    control instanceof HTMLInputElement && control.type === 'file'
                    && control.name === fileRecord.name && !control.disabled);
                const input = inputs[fileRecord.ordinal];
                if (!input) return;

                const transfer = new DataTransfer();
                fileRecord.items.forEach((file) => {
                    transfer.items.add(new File([file.blob], file.name, {
                        type: file.type,
                        lastModified: file.lastModified,
                    }));
                });
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        };

        const restoreDynamicRows = (form, dynamicCounts) => {
            const lines = form.querySelector('#lines');
            const addLineButton = form.querySelector('#add-line');
            const desiredLines = dynamicCounts?.billingLines;
            if (lines && addLineButton && Number.isInteger(desiredLines) && desiredLines > 0) {
                while (lines.querySelectorAll('.billing-line').length > desiredLines) {
                    const removeButton = lines.querySelector('.billing-line:last-child .remove-line');
                    if (!removeButton) break;
                    removeButton.click();
                }
                while (lines.querySelectorAll('.billing-line').length < desiredLines) {
                    addLineButton.click();
                }
            }

            const requirements = form.querySelector('#project-requirements');
            const addRequirementButton = form.querySelector('#add-project-requirement');
            const desiredRequirements = dynamicCounts?.projectRequirements;
            if (requirements && addRequirementButton && Number.isInteger(desiredRequirements)) {
                while (requirements.querySelectorAll('[data-requirement-row]').length > desiredRequirements) {
                    const removeButton = requirements.querySelector('[data-requirement-row]:last-child [data-remove-requirement]');
                    if (!removeButton) break;
                    removeButton.click();
                }
                while (requirements.querySelectorAll('[data-requirement-row]').length < desiredRequirements) {
                    addRequirementButton.click();
                }
            }
        };

        const restoreDraft = async (form, draft) => {
            restoringForms.add(form);
            let attachmentRestoreFailed = false;
            try {
                restoreDynamicRows(form, draft.dynamicCounts || {});
                try {
                    restoreFiles(form, draft.files || []);
                } catch (error) {
                    attachmentRestoreFailed = true;
                    console.error('Could not restore offline draft attachments.', error);
                }

                applyFields(form, draft.fields || []);
                form.querySelectorAll('select[name], select[data-offline-draft-field]').forEach((select) => {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                applyFields(form, draft.fields || []);
                form.querySelectorAll('[data-offline-draft-editor-for]').forEach((editor) => {
                    const source = form.elements.namedItem(editor.dataset.offlineDraftEditorFor);
                    if (source && 'value' in source) {
                        editor.innerHTML = source.value;
                        editor.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
                form.dataset.offlineDraftCreatedAt = String(draft.createdAt);
                form.dataset.offlineDraftSaved = 'true';
                form.dispatchEvent(new CustomEvent('offline-draft:restored', {
                    bubbles: true,
                    detail: { updatedAt: draft.updatedAt },
                }));

                const savedAt = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' })
                    .format(new Date(draft.updatedAt));
                setStatus(
                    form,
                    attachmentRestoreFailed
                        ? `Draft restored, but its attachments could not be restored. Choose them again.`
                        : `Restored your unsent draft from ${savedAt}. Local copies expire after 7 days.`,
                    attachmentRestoreFailed ? 'warning' : 'info',
                    true,
                );
            } finally {
                restoringForms.delete(form);
            }
        };

        const initializeForm = (form) => {
            if (!(form instanceof HTMLFormElement) || !form.dataset.offlineDraft || initializedForms.has(form)) return;
            initializedForms.add(form);
            getStatus(form);
            form.addEventListener('input', () => scheduleSave(form));
            form.addEventListener('change', () => scheduleSave(form));
            form.addEventListener('click', (event) => {
                if (event.target instanceof Element && event.target.closest('button[type="button"]')) {
                    scheduleSave(form);
                }
            });

            getDraft(getDraftKey(form)).then(async (draft) => {
                if (!draft) return;
                if (draft.expiresAt <= Date.now()) {
                    await deleteDraftByKey(draft.key);
                    return;
                }
                await restoreDraft(form, draft);
            }).catch((error) => {
                console.error('Could not restore the offline draft.', error);
                setStatus(form, 'Could not read this browser’s local draft. Your current form is unchanged.', 'danger');
            });
        };

        const initializeForms = (root = document) => {
            if (root instanceof HTMLFormElement) initializeForm(root);
            root.querySelectorAll?.('form[data-offline-draft]').forEach(initializeForm);
        };

        const finishPendingSubmission = async () => {
            let pendingKey;
            try {
                pendingKey = window.sessionStorage.getItem(pendingSubmissionKey);
                if (!pendingKey) return;
                window.sessionStorage.removeItem(pendingSubmissionKey);
            } catch (error) {
                console.error('Could not check the pending offline draft submission.', error);
                return;
            }

            const draftFormIsStillOpen = Array.from(document.querySelectorAll('form[data-offline-draft]'))
                .some((form) => getDraftKey(form) === pendingKey);
            if (!draftFormIsStillOpen) {
                await deleteDraftByKey(pendingKey).catch((error) => {
                    console.error('Could not remove the submitted offline draft.', error);
                });
            }
        };

        const handleSubmit = (event) => {
            const form = event.target instanceof HTMLFormElement ? event.target : null;
            if (!form?.dataset.offlineDraft || event.defaultPrevented) return;

            if (form.dataset.offlineDraftBypass === 'true') {
                delete form.dataset.offlineDraftBypass;
                try {
                    window.sessionStorage.setItem(pendingSubmissionKey, getDraftKey(form));
                } catch (error) {
                    console.error('Could not track the pending offline draft submission.', error);
                }
                return;
            }

            if (!navigator.onLine) {
                event.preventDefault();
                event.stopImmediatePropagation();
                saveDraft(form).then(() => {
                    setStatus(form, 'You are offline. Your draft is saved on this device; reconnect, then submit it manually.', 'warning', true);
                }).catch((error) => {
                    console.error('Could not save the offline draft.', error);
                    setStatus(form, 'You are offline and the local backup failed. Keep this page open; nothing was submitted.', 'danger');
                });
                return;
            }

            const isAjaxForm = form.closest('[data-project-ajax-root]') || form.matches('[data-ticket-ajax-form]');
            if (isAjaxForm || form.method.toLowerCase() === 'get'
                || (form.target && form.target.toLowerCase() !== '_self')) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            const submitter = event.submitter instanceof HTMLButtonElement || event.submitter instanceof HTMLInputElement
                ? event.submitter
                : null;
            const continueSubmission = () => {
                if (!navigator.onLine) {
                    setStatus(form, 'You are offline. Your draft is saved on this device; reconnect, then submit it manually.', 'warning', true);
                    return;
                }
                form.dataset.offlineDraftBypass = 'true';
                if (submitter) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
            };

            saveDraft(form).then(continueSubmission).catch((error) => {
                console.error('Could not save the offline draft before submission.', error);
                setStatus(form, 'Could not save a local backup. The form will still be submitted while online.', 'warning');
                continueSubmission();
            });
        };

        const initialize = () => {
            initializeForms();
            finishPendingSubmission().catch((error) => {
                console.error('Could not finish the pending offline draft submission.', error);
            });
            removeExpiredDrafts().catch((error) => {
                console.error('Could not clean up expired offline drafts.', error);
            });

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node instanceof Element) initializeForms(node);
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        };

        document.addEventListener('submit', handleSubmit, true);
        window.addEventListener('offline', () => {
            document.querySelectorAll('form[data-offline-draft]').forEach((form) => {
                saveAndShowStatus(form);
            });
        });
        window.addEventListener('online', () => {
            document.querySelectorAll('form[data-offline-draft]').forEach((form) => {
                if (form.dataset.offlineDraftSaved === 'true') {
                    setStatus(form, 'Back online. Your saved draft is ready; submit it when you are ready.', 'success', true);
                }
            });
        });
        window.addEventListener('pageshow', () => {
            finishPendingSubmission().catch((error) => {
                console.error('Could not finish the pending offline draft submission.', error);
            });
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => window.setTimeout(initialize, 0), { once: true });
        } else {
            window.setTimeout(initialize, 0);
        }

        window.offlineDrafts = {
            clear: clearDraft,
            save: saveDraft,
            setStatus,
        };
    })();
</script>
