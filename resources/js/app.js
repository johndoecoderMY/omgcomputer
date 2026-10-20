import './bootstrap';

import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Alpine = Alpine;
window.Pusher = Pusher;

Alpine.start();

const headerPageSearch = document.querySelector('[data-header-page-search]');

if (headerPageSearch) {
    const input = headerPageSearch.querySelector('.search-input');
    const form = headerPageSearch.querySelector('form');
    const links = Array.from(headerPageSearch.querySelectorAll('[data-header-search-item]'));
    const prompt = headerPageSearch.querySelector('[data-header-search-prompt]');
    const emptyState = headerPageSearch.querySelector('[data-header-search-empty]');

    const filterPages = () => {
        const query = input.value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        links.forEach((link) => {
            const searchableText = `${link.textContent} ${link.dataset.searchTerms || ''}`.toLocaleLowerCase();
            const matches = query.length > 0
                && query.split(/\s+/).every((term) => searchableText.includes(term));

            link.classList.toggle('d-none', !matches);
            visibleCount += matches ? 1 : 0;
        });

        prompt.classList.toggle('d-none', query.length > 0);
        emptyState.classList.toggle('d-none', query.length === 0 || visibleCount > 0);
    };

    input.addEventListener('input', filterPages);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        headerPageSearch.querySelector('[data-header-search-item]:not(.d-none)')?.click();
    });
    filterPages();
}

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
