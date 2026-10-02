// Shows an offline page when a page load fails. Nothing else is cached.
// Change CACHE when Theme/offline.html is edited.
const CACHE = 'emoncms-offline-1';
const OFFLINE_URL = 'Theme/offline.html';

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.add(OFFLINE_URL)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

// Page loads only. All other requests go to the network as normal.
self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(
        fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
});
