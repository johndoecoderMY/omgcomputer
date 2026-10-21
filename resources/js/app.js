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
    const recentSection = headerPageSearch.querySelector('[data-header-search-recent-section]');
    const recentList = headerPageSearch.querySelector('[data-header-search-recent]');
    const recentEmpty = headerPageSearch.querySelector('[data-header-search-recent-empty]');
    const pagesHeading = headerPageSearch.querySelector('[data-header-search-pages-heading]');
    const dismissButton = headerPageSearch.querySelector('[data-bs-dismiss="search"]');
    const recentStorageKey = 'omgcomputer.recent-search-pages';

    const readRecentPages = () => {
        try {
            const stored = JSON.parse(window.sessionStorage.getItem(recentStorageKey) || '[]');

            return Array.isArray(stored)
                ? stored.filter((item) => item && typeof item.href === 'string' && typeof item.title === 'string')
                : [];
        } catch (error) {
            console.warn('Could not read recent page history.', error);

            return [];
        }
    };

    const rememberPage = (href, title) => {
        const recentPages = readRecentPages().filter((recent) => recent.href !== href);
        recentPages.unshift({ href, title });
        try {
            window.sessionStorage.setItem(recentStorageKey, JSON.stringify(recentPages.slice(0, 5)));
        } catch (error) {
            console.warn('Could not save recent page history.', error);
        }
    };

    const renderRecentPages = () => {
        const recentPages = readRecentPages()
            .filter((recent) => links.some((link) => link.href === recent.href))
            .slice(0, 5);
        recentList.replaceChildren();
        recentPages.forEach((recent) => {
            const link = document.createElement('a');
            link.className = 'dropdown-item fs-10 px-x1 py-1 hover-primary';
            link.href = recent.href;
            link.setAttribute('role', 'option');
            const row = document.createElement('div');
            row.className = 'd-flex align-items-center';
            const icon = document.createElement('span');
            icon.className = 'fas fa-circle me-2 text-300 fs-11';
            icon.setAttribute('aria-hidden', 'true');
            const title = document.createElement('span');
            title.className = 'fw-normal title';
            title.textContent = recent.title;
            row.append(icon, title);
            link.append(row);
            recentList.append(link);
        });
        recentEmpty.classList.toggle('d-none', recentPages.length > 0);
    };

    const filterPages = () => {
        const query = input.value.trim().toLocaleLowerCase();
        let visibleCount = 0;
        const hasQuery = query.length > 0;

        links.forEach((link) => {
            const searchableText = `${link.textContent} ${link.dataset.searchTerms || ''}`.toLocaleLowerCase();
            const matches = hasQuery
                && query.split(/\s+/).every((term) => searchableText.includes(term));

            link.classList.toggle('d-none', !matches);
            visibleCount += matches ? 1 : 0;
        });

        prompt.classList.toggle('d-none', hasQuery);
        emptyState.classList.toggle('d-none', !hasQuery || visibleCount > 0);
        pagesHeading.classList.toggle('d-none', !hasQuery);
        recentSection.classList.toggle('d-none', hasQuery);
    };

    headerPageSearch.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (link && headerPageSearch.contains(link)) {
            rememberPage(link.href, link.textContent.trim());
        }
    });

    renderRecentPages();
    input.addEventListener('input', filterPages);
    input.addEventListener('focus', filterPages);
    dismissButton.addEventListener('search.close', filterPages);
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

        if (count) {
            const unreadCount = Number.parseInt(count.textContent, 10) || 0;
            const nextUnreadCount = unreadCount + 1;
            count.textContent = nextUnreadCount > 99 ? '99+' : String(nextUnreadCount);
            count.setAttribute('aria-label', `${nextUnreadCount} unread notifications`);
            count.classList.remove('d-none');
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
            bell.className = 'fas fa-bell';
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
            bellIcon.className = 'fas fa-bell me-2 text-primary';
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
