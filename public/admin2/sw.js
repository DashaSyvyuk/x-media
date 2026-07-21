/* X-media Admin service worker — icons live under /admin2/pwa/icons/ */
const CACHE_VERSION = 'admin-v8';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PRECACHE_URLS = [
    '/admin/',
    '/admin2/manifest.webmanifest',
    '/css/admin2/app.css',
    '/css/admin2/mobile.css',
    '/css/admin2/products.css',
    '/css/admin2/orders.css',
    '/css/admin2/product-edit.css',
    '/js/admin2-sidebar.js',
    '/js/admin2-pwa.js',
    '/admin2/pwa/icons/icon-192.png',
    '/admin2/pwa/icons/icon-512.png',
    '/admin2/pwa/icons/icon-maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => (key.startsWith('admin-') || key.startsWith('admin2-')) && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('push', (event) => {
    let data = {
        title: 'X-media Admin',
        body: 'Нове сповіщення',
        url: '/admin/',
        tag: 'admin-push',
    };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch (e) {
        try {
            const text = event.data ? event.data.text() : '';
            if (text) {
                data.body = text;
            }
        } catch (ignored) {
            /* keep defaults */
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'X-media Admin', {
            body: data.body || '',
            icon: '/admin2/pwa/icons/icon-192.png',
            badge: '/admin2/pwa/icons/icon-192.png',
            data: { url: data.url || '/admin/' },
            tag: data.tag || 'admin-push',
            renotify: true,
            vibrate: [120, 60, 120],
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/admin/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client && client.url.includes('/admin')) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
            return undefined;
        }),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    const isAdminPage = url.pathname.startsWith('/admin') && !url.pathname.startsWith('/admin2');
    const isAdminAsset = url.pathname.startsWith('/css/admin2/') || url.pathname.startsWith('/js/admin2');

    if (!isAdminPage && !isAdminAsset) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => response)
                .catch(() => caches.match('/admin/')),
        );
        return;
    }

    if (isAdminAsset) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(async () => (await cache.match(request)) || Response.error());

                return network;
            }),
        );
    }
});
