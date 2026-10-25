import './bootstrap';

import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

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

if (userId && pusherKey && pusherCluster) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
    });

    window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
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
}
