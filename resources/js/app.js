import './bootstrap';

import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import * as PusherPushNotifications from '@pusher/push-notifications-web';

window.Alpine = Alpine;
window.Pusher = Pusher;

Alpine.start();

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-cart-add-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    if (button?.disabled) {
        return;
    }

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json();

        if (!response.ok) {
            const messages = Object.values(payload.errors || {}).flat();
            throw new Error(messages.join('\n') || payload.message || 'Could not add this product to the cart.');
        }

        const cartLink = document.querySelector('[data-cart-count]');
        const cartCount = Number(payload.cartCount);
        if (!Number.isInteger(cartCount) || cartCount < 0) {
            throw new Error('The cart was updated, but its item count could not be read. Refresh the page to see the latest count.');
        }

        if (cartLink) {
            const count = cartLink.querySelector('.notification-indicator-number');
            if (count) {
                count.textContent = cartCount > 99 ? '99+' : String(cartCount);
            }
            cartLink.setAttribute('aria-label', `Shopping cart, ${cartCount} ${cartCount === 1 ? 'item' : 'items'}`);
        }

        window.showAppToast?.(payload.message || 'Product added to cart.', 'success');
    } catch (error) {
        window.showAppToast?.(error instanceof Error ? error.message : 'Could not add this product to the cart.', 'danger');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
});

const page = document.body;
const userId = page.dataset.notificationsUser;
const pusherKey = page.dataset.pusherKey;
const pusherCluster = page.dataset.pusherCluster;
const readPrefix = page.dataset.notificationReadPrefix;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const beamsInstanceId = page.dataset.beamsInstanceId;
const beamsAuthUrl = page.dataset.beamsAuthUrl;
const beamsPushEnabled = page.dataset.beamsPushEnabled === 'true';
const needsServiceWorker = userId && (beamsInstanceId || page.dataset.staffPwa === 'true');
const serviceWorkerPath = beamsInstanceId ? '/service-worker.js' : '/staff-service-worker.js';
const serviceWorkerPromise = needsServiceWorker && 'serviceWorker' in navigator
    ? navigator.serviceWorker.register(serviceWorkerPath, { scope: '/', updateViaCache: 'none' })
        .catch((error) => {
            console.error('Application service worker registration failed.', error);
            return null;
        })
    : Promise.resolve(null);

if (page.dataset.staffPwa === 'true') {
    serviceWorkerPromise.then((registration) => {
        if (!registration) {
            return;
        }

        let updateRequested = false;
        let updateBanner = null;
        const showUpdateBanner = (waitingWorker) => {
            if (!waitingWorker || !navigator.serviceWorker.controller || updateBanner) {
                return;
            }

            updateBanner = document.createElement('div');
            updateBanner.className = 'alert alert-primary d-flex align-items-center gap-3 position-fixed bottom-0 start-50 translate-middle-x m-3 shadow';
            updateBanner.setAttribute('role', 'status');
            updateBanner.style.zIndex = '1080';

            const message = document.createElement('span');
            message.textContent = 'A staff app update is ready. Save your work before applying it.';
            const updateButton = document.createElement('button');
            updateButton.className = 'btn btn-primary btn-sm text-nowrap';
            updateButton.type = 'button';
            updateButton.textContent = 'Update now';
            updateButton.addEventListener('click', () => {
                updateRequested = true;
                updateButton.disabled = true;
                updateButton.textContent = 'Updating…';
                waitingWorker.postMessage({ type: 'ACTIVATE_STAFF_PWA_UPDATE' });
            });

            updateBanner.append(message, updateButton);
            document.body.append(updateBanner);
        };
        const checkWaitingWorker = () => showUpdateBanner(registration.waiting);
        const observeUpdate = () => {
            const installing = registration.installing;
            installing?.addEventListener('statechange', () => {
                if (installing.state === 'installed') {
                    checkWaitingWorker();
                }
            });
        };

        registration.addEventListener('updatefound', observeUpdate);
        checkWaitingWorker();
        observeUpdate();
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (updateRequested) {
                window.location.reload();

                return;
            }

            checkWaitingWorker();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                registration.update().catch((error) => {
                    console.error('Staff app update check failed.', error);
                });
            }
        });
    });
}

if (page.dataset.staffPwa === 'true') {
    const installButton = document.querySelector('[data-staff-pwa-install]');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    let installPrompt = null;

    if (isStandalone && installButton) {
        installButton.hidden = true;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
    });

    installButton?.addEventListener('click', async () => {
        if (!installPrompt) {
            const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent)
                || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            window.showAppToast?.(
                isIos
                    ? 'In Safari, tap Share, then Add to Home Screen.'
                    : 'Use your browser menu to install the app or add it to your home screen.',
                'info',
            );

            return;
        }

        const prompt = installPrompt;
        installPrompt = null;
        try {
            await prompt.prompt();
            await prompt.userChoice;
        } catch (error) {
            console.error('Staff app installation prompt failed.', error);
        }
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        if (installButton) {
            installButton.hidden = true;
        }
    });
}

let beamsClientPromise = null;

if (userId && beamsInstanceId && beamsAuthUrl) {
    beamsClientPromise = serviceWorkerPromise.then(async (registration) => {
        if (!registration) {
            return null;
        }

        const client = new PusherPushNotifications.Client({
            instanceId: beamsInstanceId,
            serviceWorkerRegistration: registration,
        });
        const registeredUserId = await client.getUserId();

        if (!beamsPushEnabled) {
            if (registeredUserId) {
                await client.stop();
            }

            return client;
        }

        if (registeredUserId && registeredUserId !== String(userId)) {
            await client.stop();
        }

        if ('Notification' in window
            && Notification.permission === 'granted'
            && registeredUserId !== String(userId)) {
            await client.start();
            if (await client.getUserId() !== String(userId)) {
                const tokenProvider = new PusherPushNotifications.TokenProvider({ url: beamsAuthUrl });
                await client.setUserId(String(userId), tokenProvider);
            }
        }

        return client;
    }).catch((error) => {
        console.error('Pusher Beams could not initialize.', error);
        return null;
    });

    const enablePushButton = document.querySelector('[data-enable-push-notifications]');
    if (enablePushButton) {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            enablePushButton.disabled = true;
            enablePushButton.textContent = 'Push notifications are not supported';
        } else if (Notification.permission === 'denied') {
            enablePushButton.disabled = true;
            enablePushButton.textContent = 'Allow notifications in browser settings';
        } else {
            enablePushButton.addEventListener('click', async () => {
                enablePushButton.disabled = true;
                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        enablePushButton.disabled = permission === 'denied';
                        if (permission === 'denied') {
                            enablePushButton.textContent = 'Allow notifications in browser settings';
                        }
                        window.showAppToast?.('Push notification permission was not granted.', 'info');

                        return;
                    }

                    const client = await beamsClientPromise;
                    if (!client) {
                        throw new Error('The push notification service worker could not be initialized.');
                    }

                    if (await client.getUserId() !== String(userId)) {
                        await client.start();
                        const tokenProvider = new PusherPushNotifications.TokenProvider({ url: beamsAuthUrl });
                        await client.setUserId(String(userId), tokenProvider);
                    }
                    enablePushButton.textContent = 'Push notifications enabled';
                    window.showAppToast?.('Push notifications are enabled for this browser.', 'success');
                } catch (error) {
                    console.error('Could not enable Pusher Beams notifications.', error);
                    enablePushButton.disabled = false;
                    window.showAppToast?.('Could not enable push notifications. Check this site’s browser permissions and try again.', 'danger');
                }
            });

            beamsClientPromise.then(async (client) => {
                if (client && await client.getUserId() === String(userId)) {
                    enablePushButton.textContent = 'Push notifications enabled';
                    enablePushButton.disabled = true;
                }
            }).catch((error) => console.error('Could not read Pusher Beams registration state.', error));
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.action || !beamsClientPromise) {
            return;
        }

        const logoutPath = new URL(form.action, window.location.href).pathname;
        if (!logoutPath.endsWith('/logout') || form.dataset.beamsLogoutPending === 'true') {
            return;
        }

        event.preventDefault();
        form.dataset.beamsLogoutPending = 'true';
        beamsClientPromise
            .then((client) => client?.stop())
            .catch((error) => console.error('Could not clear Pusher Beams registration during logout.', error))
            .finally(() => form.submit());
    });
}

if (userId && pusherKey && pusherCluster && csrfToken) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });

    const notificationsChannel = window.Echo.private(`App.Models.User.${userId}`);
    notificationsChannel.error((error) => {
        console.error('Pusher notification channel subscription failed.', error);
    });
    notificationsChannel.notification((notification) => {
        const list = document.getElementById('navbar-notifications-list');
        const count = document.getElementById('unread-notifications-count');

        if (count) {
            const unreadCount = Number.parseInt(count.textContent, 10) || 0;
            const nextUnreadCount = unreadCount + 1;
            count.textContent = nextUnreadCount > 99 ? '99+' : String(nextUnreadCount);
            count.setAttribute('aria-label', `${nextUnreadCount} unread notifications`);
            count.classList.remove('d-none');
            const notificationLink = document.getElementById('navbarDropdownNotification');
            notificationLink?.classList.add(
                'notification-indicator',
                'notification-indicator-primary',
                'notification-indicator-fill',
            );
            notificationLink?.setAttribute('aria-label', `Notifications, ${nextUnreadCount} unread`);
        }

        if (list) {
            document.getElementById('navbar-notifications-empty')?.remove();

            let group = list.querySelector('.list-group');
            if (!group) {
                group = document.createElement('div');
                group.className = 'list-group list-group-flush fw-normal fs-10';
                list.append(group);
            }

            if (!group.querySelector('[data-notification-new-title]')) {
                const title = document.createElement('div');
                title.className = 'list-group-title border-bottom px-3 py-2';
                title.dataset.notificationNewTitle = '';
                title.textContent = 'NEW';
                group.prepend(title);
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'list-group-item p-0';
            wrapper.dataset.notificationItem = '';
            const item = document.createElement('a');
            item.className = 'notification notification-flush notification-unread';
            item.href = `${readPrefix}/${encodeURIComponent(notification.id)}/read`;

            const avatar = document.createElement('div');
            avatar.className = 'notification-avatar';
            const avatarSize = document.createElement('div');
            avatarSize.className = 'avatar avatar-2xl me-3';
            const avatarName = document.createElement('div');
            avatarName.className = 'avatar-name rounded-circle bg-primary-subtle text-primary';
            const bell = document.createElement('span');
            bell.className = 'bi bi-bell';
            bell.setAttribute('aria-hidden', 'true');
            avatarName.append(bell);
            avatarSize.append(avatarName);
            avatar.append(avatarSize);

            const body = document.createElement('div');
            body.className = 'notification-body';
            const paragraph = document.createElement('p');
            paragraph.className = 'mb-1';
            const heading = document.createElement('strong');
            heading.textContent = notification.title || 'Workspace update';
            paragraph.append(heading, document.createTextNode(` ${notification.message || ''}`));
            const time = document.createElement('span');
            time.className = 'notification-time';
            const bellIcon = document.createElement('span');
            bellIcon.className = 'bi bi-bell me-2 text-primary';
            bellIcon.setAttribute('aria-hidden', 'true');
            const timeText = document.createElement('span');
            timeText.textContent = 'Just now';
            time.append(bellIcon, timeText);
            body.append(paragraph, time);

            item.append(avatar, body);
            wrapper.append(item);
            group.insertBefore(wrapper, group.children[1] || null);
            group.querySelectorAll('[data-notification-item]').forEach((entry, index) => {
                if (index > 5) entry.remove();
            });
        }

    });
} else if (userId) {
    console.error('Realtime notifications could not start because Pusher or CSRF configuration is missing.');
}
