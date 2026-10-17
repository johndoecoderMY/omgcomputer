import './bootstrap';

import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Alpine = Alpine;
window.Pusher = Pusher;

Alpine.start();

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

        if (list && count) {
            document.getElementById('navbar-notifications-empty')?.remove();

            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action bg-body-tertiary';
            item.href = `${readPrefix}/${encodeURIComponent(notification.id)}/read`;
            item.dataset.notificationItem = '';

            const heading = document.createElement('strong');
            heading.className = 'small d-block';
            heading.textContent = notification.title || 'Workspace update';
            const message = document.createElement('span');
            message.className = 'small text-body-secondary d-block mt-1';
            message.textContent = notification.message || '';
            const time = document.createElement('span');
            time.className = 'small text-body-tertiary';
            time.textContent = 'Just now';

            item.append(heading, message, time);
            list.prepend(item);
            list.querySelectorAll('[data-notification-item]').forEach((entry, index) => {
                if (index > 5) entry.remove();
            });

            const unreadCount = Number.parseInt(count.textContent, 10) || 0;
            count.textContent = unreadCount >= 99 ? '99+' : String(unreadCount + 1);
            count.classList.remove('d-none');
        }
    });
}
