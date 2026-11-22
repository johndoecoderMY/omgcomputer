import './bootstrap';

import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import * as PusherPushNotifications from '@pusher/push-notifications-web';
import './booking-phone';

window.Alpine = Alpine;
window.Pusher = Pusher;

Alpine.start();

const initializeActiveSidebarLink = () => {
    const sidebar = document.getElementById('navbarVerticalNav');
    if (!sidebar) {
        return;
    }

    const normalizePath = (path) => path.replace(/\/+$/, '') || '/';
    const currentUrl = new URL(window.location.href);
    const currentPath = normalizePath(currentUrl.pathname);
    const links = Array.from(sidebar.querySelectorAll('a.nav-link[href]'))
        .filter((link) => !link.getAttribute('href').startsWith('#'))
        .map((link) => {
            const url = new URL(link.href, currentUrl.origin);
            return url.origin === currentUrl.origin
                ? { link, url, path: normalizePath(url.pathname) }
                : null;
        })
        .filter(Boolean);
    const matchesQuery = ({ url }) => Array.from(url.searchParams.entries())
        .every(([key, value]) => currentUrl.searchParams.getAll(key).includes(value));
    const exactPathLinks = links.filter(({ path }) => path === currentPath);
    let activeLinks = exactPathLinks.filter(matchesQuery);
    let relatedLinks = activeLinks.length ? activeLinks : exactPathLinks;

    if (exactPathLinks.length === 0) {
        const prefixLinks = links.filter(({ path }) => (
            path !== '/' && currentPath.startsWith(`${path}/`)
        ));
        const matchingPrefixLinks = prefixLinks.filter(matchesQuery);
        const candidates = matchingPrefixLinks.length ? matchingPrefixLinks : prefixLinks;

        if (candidates.length) {
            const longestPath = Math.max(...candidates.map(({ path }) => path.length));
            relatedLinks = candidates.filter(({ path }) => path.length === longestPath);
            activeLinks = matchingPrefixLinks.length ? relatedLinks : [];
        }
    }

    activeLinks.forEach(({ link }) => {
        link.classList.add('active', 'bg-primary-subtle', 'fw-semibold');
        link.setAttribute('aria-current', 'page');
    });

    relatedLinks.forEach(({ link }) => {
        let collapse = link.closest('.nav.collapse');

        while (collapse) {
            collapse.classList.add('show');
            const trigger = Array.from(sidebar.querySelectorAll('[aria-controls]'))
                .find((element) => element.getAttribute('aria-controls') === collapse.id);

            if (trigger) {
                trigger.classList.add('active', 'text-primary', 'fw-semibold');
                trigger.setAttribute('aria-expanded', 'true');
            }

            collapse = collapse.parentElement?.closest('.nav.collapse') || null;
        }
    });
};

initializeActiveSidebarLink();

const modalTriggers = new WeakMap();

document.addEventListener('show.bs.modal', (event) => {
    const modal = event.target;
    const trigger = event.relatedTarget;

    if (modal instanceof HTMLElement && trigger instanceof HTMLElement) {
        modalTriggers.set(modal, trigger);
    }
}, true);

document.addEventListener('hide.bs.modal', (event) => {
    const modal = event.target;
    if (!(modal instanceof HTMLElement)) {
        return;
    }

    const trigger = modalTriggers.get(modal);
    if (!trigger) {
        return;
    }

    const dropdownToggle = trigger.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
    const focusTarget = trigger.getClientRects().length > 0 ? trigger : dropdownToggle;
    if (!(focusTarget instanceof HTMLElement)) {
        return;
    }

    window.requestAnimationFrame(() => {
        if (focusTarget.isConnected) {
            focusTarget.focus({ preventScroll: true });
        }
    });
}, true);

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
const notificationSoundStorageKey = userId ? `omg-notification-sounds:${userId}` : null;
let notificationAudioContext = null;
let notificationAudioUnlocked = false;
let notificationSoundErrorLogged = false;
const notificationSoundTabId = `${Date.now()}-${Math.random()}`;
const pendingNotificationSoundIds = new Set();
let flushPendingNotificationSounds = () => {};
const logNotificationSoundError = (error) => {
    if (notificationSoundErrorLogged) {
        return;
    }

    notificationSoundErrorLogged = true;
    console.error('Could not play or coordinate notification sound.', error);
};
const prepareNotificationAudio = async () => {
    if (!window.AudioContext) {
        logNotificationSoundError(new Error('Web Audio is not supported in this browser.'));
        return null;
    }

    if (!notificationAudioUnlocked) {
        return null;
    }

    try {
        notificationAudioContext ||= new window.AudioContext();
        if (notificationAudioContext.state !== 'running') {
            await notificationAudioContext.resume();
        }

        return notificationAudioContext.state === 'running' ? notificationAudioContext : null;
    } catch (error) {
        logNotificationSoundError(error);
        return null;
    }
};
const playNotificationChime = async () => {
    const audioContext = await prepareNotificationAudio();
    if (!audioContext) {
        return false;
    }

    return new Promise((resolve) => {
        try {
            const startAt = audioContext.currentTime + 0.01;
            const oscillator = audioContext.createOscillator();
            const volume = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, startAt);
            oscillator.frequency.setValueAtTime(1174, startAt + 0.12);
            volume.gain.setValueAtTime(0.0001, startAt);
            volume.gain.exponentialRampToValueAtTime(0.08, startAt + 0.02);
            volume.gain.setValueAtTime(0.08, startAt + 0.12);
            volume.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.29);
            oscillator.connect(volume);
            volume.connect(audioContext.destination);
            oscillator.addEventListener('ended', () => resolve(true), { once: true });
            oscillator.start(startAt);
            oscillator.stop(startAt + 0.3);
        } catch (error) {
            logNotificationSoundError(error);
            resolve(false);
        }
    });
};
if (userId) {
    const unlockNotificationAudio = () => {
        if (!window.AudioContext) {
            logNotificationSoundError(new Error('Web Audio is not supported in this browser.'));
            return;
        }

        if (notificationAudioUnlocked && notificationAudioContext?.state === 'running') {
            return;
        }

        try {
            notificationAudioContext ||= new window.AudioContext();
            notificationAudioContext.resume()
                .then(() => {
                    notificationAudioUnlocked = notificationAudioContext?.state === 'running';
                    if (notificationAudioUnlocked) {
                        void flushPendingNotificationSounds();
                    }
                })
                .catch((error) => {
                    logNotificationSoundError(error);
                });
        } catch (error) {
            logNotificationSoundError(error);
        }
    };
    document.addEventListener('pointerdown', unlockNotificationAudio, { passive: true });
    document.addEventListener('keydown', unlockNotificationAudio);
}
const readPlayedNotificationIds = () => {
    try {
        const storedIds = JSON.parse(localStorage.getItem(notificationSoundStorageKey) || '[]');
        return Array.isArray(storedIds) ? storedIds.filter((id) => typeof id === 'string').slice(-50) : [];
    } catch (error) {
        logNotificationSoundError(error);
        return null;
    }
};
const playNotificationSoundOnce = async (notificationId) => {
    const id = typeof notificationId === 'string' ? notificationId : '';
    if (!notificationSoundStorageKey || !id) {
        return;
    }

    if (!notificationAudioUnlocked) {
        if (pendingNotificationSoundIds.size < 20) {
            pendingNotificationSoundIds.add(id);
        }
        return;
    }

    const playIfNew = async () => {
        const playedIds = readPlayedNotificationIds();
        if (!playedIds || playedIds.includes(id)) {
            return;
        }

        if (await playNotificationChime()) {
            try {
                localStorage.setItem(
                    notificationSoundStorageKey,
                    JSON.stringify([...playedIds, id].slice(-50)),
                );
            } catch (error) {
                logNotificationSoundError(error);
            }
        }
    };

    if (navigator.locks?.request) {
        try {
            await navigator.locks.request(`omg-notification-sound:${userId}`, playIfNew);
        } catch (error) {
            logNotificationSoundError(error);
        }

        return;
    }

    const lockKey = `${notificationSoundStorageKey}:lock`;
    try {
        const currentLock = JSON.parse(localStorage.getItem(lockKey) || 'null');
        if (currentLock?.expiresAt > Date.now()) {
            return;
        }

        localStorage.setItem(lockKey, JSON.stringify({
            owner: notificationSoundTabId,
            expiresAt: Date.now() + 1500,
        }));
        await new Promise((resolve) => setTimeout(resolve, 30));
        const acquiredLock = JSON.parse(localStorage.getItem(lockKey) || 'null');
        if (acquiredLock?.owner !== notificationSoundTabId) {
            return;
        }

        try {
            await playIfNew();
        } finally {
            const latestLock = JSON.parse(localStorage.getItem(lockKey) || 'null');
            if (latestLock?.owner === notificationSoundTabId) {
                localStorage.removeItem(lockKey);
            }
        }
    } catch (error) {
        logNotificationSoundError(error);
    }
};
let isFlushingNotificationSounds = false;
flushPendingNotificationSounds = async () => {
    if (!notificationAudioUnlocked || isFlushingNotificationSounds) {
        return;
    }

    isFlushingNotificationSounds = true;
    try {
        while (notificationAudioUnlocked && pendingNotificationSoundIds.size > 0) {
            const [notificationId] = pendingNotificationSoundIds;
            pendingNotificationSoundIds.delete(notificationId);
            await playNotificationSoundOnce(notificationId);
        }
    } finally {
        isFlushingNotificationSounds = false;
    }
};
window.playAppNotificationSoundOnce = playNotificationSoundOnce;
const serviceWorkerPromise = needsServiceWorker && 'serviceWorker' in navigator
    ? navigator.serviceWorker.register(serviceWorkerPath, { scope: '/', updateViaCache: 'none' })
        .catch((error) => {
            console.error('Application service worker registration failed.', error);
            return null;
        })
    : Promise.resolve(null);

if (userId) {
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-notification-read-all]')) {
            return;
        }

        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        if (button?.disabled) return;

        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        }

        let updated = false;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            });
            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error(`Could not update notifications (HTTP ${response.status}).`);
            }

            if (!response.ok) {
                throw new Error(result.message || `Could not update notifications (HTTP ${response.status}).`);
            }
            if (!Number.isInteger(result.unread_count) || result.unread_count < 0) {
                throw new Error('The unread notification count was not returned.');
            }

            const count = document.getElementById('unread-notifications-count');
            const notificationLink = document.getElementById('navbarDropdownNotification');
            if (count) {
                count.textContent = result.unread_count > 99 ? '99+' : String(result.unread_count);
                count.setAttribute('aria-label', `${result.unread_count} unread notifications`);
                count.classList.toggle('d-none', result.unread_count === 0);
            }
            if (notificationLink) {
                const hasUnread = result.unread_count > 0;
                notificationLink.classList.toggle('notification-indicator', hasUnread);
                notificationLink.classList.toggle('notification-indicator-primary', hasUnread);
                notificationLink.classList.toggle('notification-indicator-fill', hasUnread);
                notificationLink.setAttribute('aria-label', `Notifications, ${result.unread_count} unread`);
            }

            document.querySelectorAll('.notification-unread').forEach((notification) => {
                notification.classList.remove('notification-unread');
            });
            document.querySelectorAll('[data-notification-new-title]').forEach((title) => title.remove());

            const notificationGroup = document.querySelector('#navbar-notifications-list .list-group');
            const firstItem = notificationGroup?.querySelector('[data-notification-item]');
            if (notificationGroup && firstItem && !notificationGroup.querySelector('.list-group-title')) {
                const earlierTitle = document.createElement('div');
                earlierTitle.className = 'list-group-title border-bottom px-3 py-2';
                earlierTitle.textContent = 'EARLIER';
                notificationGroup.insertBefore(earlierTitle, firstItem);
            }

            document.querySelectorAll('[data-notification-read-all] button[type="submit"]').forEach((submitButton) => {
                submitButton.disabled = result.unread_count === 0;
            });
            updated = true;
            window.showAppToast?.(result.message || 'All notifications marked as read.', 'success', 'Notifications');
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Could not update notifications.';
            console.error('Could not mark all notifications as read.', error);
            window.showAppToast?.(message, 'danger', 'Notifications');
        } finally {
            if (button) {
                button.disabled = !updated;
                button.removeAttribute('aria-busy');
            }
        }
    });
}

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

const shownNotificationIds = new Set(
    Array.from(document.querySelectorAll('#navbar-notifications-list [data-notification-id]'))
        .map((item) => item.dataset.notificationId)
        .filter((id) => Boolean(id)),
);
const addAppNotificationToMenu = (notification) => {
    const id = typeof notification?.id === 'string' ? notification.id : '';
    if (!id) {
        console.error('Received a notification without an ID.');
        return;
    }
    if (shownNotificationIds.has(id)) {
        return;
    }

    shownNotificationIds.add(id);
    void playNotificationSoundOnce(id);

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

    document.querySelectorAll('[data-notification-read-all] button[type="submit"]').forEach((button) => {
        button.disabled = false;
    });

    if (!list) {
        return;
    }

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
    wrapper.dataset.notificationId = id;
    const item = document.createElement('a');
    item.className = 'notification notification-flush notification-unread';
    item.href = `${readPrefix}/${encodeURIComponent(id)}/read`;

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
    heading.textContent = typeof notification.title === 'string' && notification.title
        ? notification.title
        : 'Workspace update';
    const message = typeof notification.message === 'string' ? ` ${notification.message}` : '';
    paragraph.append(heading, document.createTextNode(message));
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
};

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-notification-test-form]')) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    if (button?.disabled) {
        return;
    }

    const label = form.querySelector('[data-notification-test-label]');
    const status = document.querySelector('[data-notification-test-result]');
    const defaultLabel = label?.textContent || '';
    const showStatus = (message, type) => {
        if (!status) {
            return;
        }
        status.textContent = message;
        status.className = `alert alert-${type} mt-3`;
    };

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }
    if (label) {
        label.textContent = 'Sending...';
    }
    showStatus('Sending test notification...', 'info');

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
        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new Error(`Could not send test notification (HTTP ${response.status}).`);
        }

        if (!response.ok || payload?.success !== true) {
            const errors = Object.values(payload?.errors || {}).flat();
            throw new Error(errors.join('\n') || payload?.message || 'The test notification could not be sent.');
        }
        if (!payload?.notification || typeof payload.notification.id !== 'string') {
            throw new Error('The notification was sent, but its details could not be displayed. Refresh the page to see it.');
        }

        addAppNotificationToMenu(payload.notification);
        showStatus(payload.message || 'A test notification was sent to your account.', 'success');
    } catch (error) {
        console.error('Could not send test notification.', error);
        showStatus(error instanceof Error ? error.message : 'The test notification could not be sent.', 'danger');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
        if (label) {
            label.textContent = defaultLabel;
        }
    }
});

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
        addAppNotificationToMenu(notification);
    });
} else if (userId) {
    console.error('Realtime notifications could not start because Pusher or CSRF configuration is missing.');
}
