/* X-media Admin service worker — icons live under /admin2/pwa/icons/ */
const CACHE_VERSION = 'admin-v3';
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
                    .filter((key) => (key.startsWith('admin-') || key.startsWith('admin2-')) && key !== STATIC_CACHE)
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
