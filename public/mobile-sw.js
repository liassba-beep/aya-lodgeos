self.CACHE_NAME = 'aya-lodgeos-mobile-v4';
self.PRECACHE_URLS = ['/mobile', '/trabalhador/login', '/trabalhador/app', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(self.CACHE_NAME).then((cache) =>
            cache.addAll(self.PRECACHE_URLS),
        ).finally(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('aya-lodgeos-mobile-') && key !== self.CACHE_NAME)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const acceptsJson = event.request.headers.get('accept')?.includes('application/json');
    const url = new URL(event.request.url);

    if (acceptsJson || url.pathname.endsWith('/novidades') || url.pathname === '/operational-alerts/latest') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const copy = response.clone();
                caches.open(self.CACHE_NAME).then((cache) => cache.put(event.request, copy));

                return response;
            })
            .catch(() => caches.match(event.request).then((response) => response || caches.match('/trabalhador/app'))),
    );
});
