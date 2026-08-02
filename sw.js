self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    return caches.delete(cache);
                })
            );
        }).then(() => {
            return self.clients.claim();
        }).then(() => {
            // Unregister the service worker completely
            self.registration.unregister();
        })
    );
});

// Pass through all fetch requests without caching
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});
