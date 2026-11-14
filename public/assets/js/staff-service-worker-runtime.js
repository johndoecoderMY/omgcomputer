(() => {
    'use strict';

    const CACHE_PREFIX = 'omg-staff-pwa-';
    // Bump this version when the offline shell or cached asset policy changes.
    const CACHE_NAME = `${CACHE_PREFIX}v1`;
    const OFFLINE_URL = '/offline.html';
    const CORE_ASSETS = [
        OFFLINE_URL,
        '/assets/css/theme.min.css',
        '/assets/css/user.min.css',
        '/assets/js/config.js',
        '/assets/js/theme.js',
        '/assets/img/omg/android-chrome-192x192.png',
        '/assets/img/omg/android-chrome-512x512.png',
        '/assets/img/omg/android-chrome-maskable-512x512.png',
    ];
    const STATIC_ASSET_PATTERN = /\.(?:css|js|woff2?|png|svg)$/i;

    async function buildAssets() {
        const response = await fetch('/build/manifest.json', { cache: 'no-store' });
        if (!response.ok) {
            throw new Error(`Could not load the Vite manifest (HTTP ${response.status}).`);
        }

        const manifest = await response.json();
        const entries = [
            manifest['resources/js/app.js'],
            manifest['resources/css/icons.css'],
        ];
        const files = entries.flatMap((entry) => {
            if (!entry || typeof entry.file !== 'string') {
                throw new Error('The Vite manifest is missing a required staff app asset.');
            }

            return [entry.file, ...(entry.css || []), ...(entry.assets || [])];
        });

        return [...new Set(files)].map((file) => {
            if (!file.startsWith('assets/') || file.includes('..') || file.includes('\\')) {
                throw new Error('The Vite manifest contains an invalid staff app asset path.');
            }

            return `/${file}`;
        });
    }

    self.addEventListener('install', (event) => {
        event.waitUntil((async () => {
            const cache = await caches.open(CACHE_NAME);
            const assets = [...new Set([...CORE_ASSETS, ...await buildAssets()])];
            await Promise.all(assets.map(async (asset) => {
                const response = await fetch(asset, { cache: 'reload' });
                if (!response.ok || response.type !== 'basic') {
                    throw new Error(`Could not cache staff app asset ${asset}.`);
                }

                await cache.put(asset, response);
            }));
        })());
    });

    self.addEventListener('activate', (event) => {
        event.waitUntil((async () => {
            const oldCaches = (await caches.keys())
                .filter((name) => name.startsWith(CACHE_PREFIX) && name !== CACHE_NAME);
            await Promise.all(oldCaches.map((name) => caches.delete(name)));
            await self.clients.claim();
        })());
    });

    self.addEventListener('message', (event) => {
        if (event.data?.type === 'ACTIVATE_STAFF_PWA_UPDATE') {
            event.waitUntil(self.skipWaiting());
        }
    });

    self.addEventListener('fetch', (event) => {
        const request = event.request;
        const url = new URL(request.url);
        if (request.method !== 'GET' || url.origin !== self.location.origin) {
            return;
        }

        if (request.mode === 'navigate') {
            event.respondWith(fetch(request).catch(async () => (
                await caches.match(OFFLINE_URL)
                    || new Response('You are offline. Reconnect and try again.', {
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                    })
            )));

            return;
        }

        const isHashedBuildAsset = url.pathname.startsWith('/build/assets/')
            && STATIC_ASSET_PATTERN.test(url.pathname);
        const isCoreAsset = CORE_ASSETS.includes(url.pathname);
        if (!isHashedBuildAsset && !isCoreAsset) {
            return;
        }

        event.respondWith((async () => {
            const cache = await caches.open(CACHE_NAME);
            try {
                const response = await fetch(request);
                if (response.ok && response.type === 'basic') {
                    await cache.put(request, response.clone());
                }

                return response;
            } catch (error) {
                const cached = await cache.match(request);
                if (cached) {
                    return cached;
                }

                throw error;
            }
        })());
    });
})();
