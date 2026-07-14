/* X-media Admin2 service worker — replace icons in /admin2/pwa/icons/ when ready */
const CACHE_VERSION = 'admin2-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PRECACHE_URLS = [
    '/admin2/manifest.webmanifest',
    '/css/admin2/app.css',
    '/css/admin2/products.css',
    '/css/admin2/orders.css',
    '/css/admin2/product-edit.css',
    '/js/admin2-sidebar.js',
    '/js/admin2-pwa.js',
    '/admin2/pwa/icons/icon-192.png',
    '/admin2/pwa/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('admin2-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || !url.pathname.startsWith('/admin2')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => response)
                .catch(() => caches.match('/admin2/')),
        );
        return;
    }

    if (url.pathname.startsWith('/css/admin2/') || url.pathname.startsWith('/js/admin2')) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => cached);

                return cached || network;
            }),
        );
    }
});
