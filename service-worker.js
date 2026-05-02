// Emoncms Service Worker for PWA support
const CACHE_NAME = 'emoncms-cache-v1';
const urlsToCache = [
  './',
  './Theme/emoncms-base.css',
  './Lib/jquery-3.6.0.min.js',
  './Lib/bootstrap/css/bootstrap.min.css',
  './Lib/bootstrap/js/bootstrap.js',
  './Lib/emoncms.js',
  './Theme/logo_normal.png',
  './Theme/pwa-icon-192.png',
  './Theme/pwa-icon-512.png'
];

// Install event - cache core assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .catch(err => {
        console.error('Cache install failed:', err);
      })
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return response
        if (response) {
          return response;
        }
        
        // Clone the request
        const fetchRequest = event.request.clone();
        
        return fetch(fetchRequest).then(response => {
          // Check if valid response
          if (!response || response.status !== 200) {
            return response;
          }
          
          // Cache basic, cors, and opaque responses
          if (response.type !== 'basic' && response.type !== 'cors' && response.type !== 'opaque') {
            return response;
          }
          
          // Clone the response
          const responseToCache = response.clone();
          
          // Cache successful responses
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });
          
          return response;
        });
      })
      .catch(() => {
        // For navigation requests (HTML pages), return the cached root page
        if (event.request.mode === 'navigate' || event.request.destination === 'document') {
          return caches.match('./');
        }
        // For other requests, return a generic offline response
        return new Response('Offline - content not available', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: new Headers({
            'Content-Type': 'text/plain'
          })
        });
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
