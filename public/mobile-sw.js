self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('aya-lodgeos-mobile-v2').then((cache) =>
            cache.addAll(['/mobile', '/trabalhador/login', '/trabalhador/app']),
        ).finally(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const copy = response.clone();
                caches.open('aya-lodgeos-mobile-v2').then((cache) => cache.put(event.request, copy));

                return response;
            })
            .catch(() => caches.match(event.request).then((response) => response || caches.match('/trabalhador/app'))),
    );
});
