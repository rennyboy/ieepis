const CACHE_NAME = 'ieepis-pwa-cache-v3';
const RUNTIME_CACHE = 'ieepis-runtime-v3';
const urlsToCache = [
  '/',
  '/offline.html',
  '/images/ieepis-logo.png',
  '/admin/qr-scanner-page',
];

// Routes that should serve the cached HTML shell when offline so the Vue app can boot
const OFFLINE_SHELL_ROUTES = [
  '/admin/offline-equipment-page',
  '/admin/qr-scanner-page',
];

// API endpoints whose JSON we cache for offline reads (stale-while-revalidate)
const OFFLINE_API_ROUTES = [
  '/equipment/offline/cache',
  '/scanner/resolve',
  '/scanner/sync',
];

// Install event - cache core assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME, RUNTIME_CACHE];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - Network First with offline fallback + JSON cache for offline endpoints
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  const isOfflineApi = OFFLINE_API_ROUTES.some((route) => url.pathname.startsWith(route));
  const isOfflineShell = OFFLINE_SHELL_ROUTES.some((route) => url.pathname.startsWith(route));

  // Stale-while-revalidate for offline-capable JSON endpoints
  if (isOfflineApi) {
    event.respondWith(
      caches.open(RUNTIME_CACHE).then(async (cache) => {
        const cached = await cache.match(event.request);
        const networkFetch = fetch(event.request)
          .then((response) => {
            if (response && response.status === 200) {
              cache.put(event.request, response.clone());
            }
            return response;
          })
          .catch(() => cached);
        return cached || networkFetch;
      })
    );
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Cache same-origin successful navigations to the offline shells so they boot offline
        if (
          isOfflineShell
          && response
          && response.status === 200
          && response.type === 'basic'
        ) {
          const clone = response.clone();
          caches.open(RUNTIME_CACHE).then((cache) => cache.put(event.request, clone));
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request).then((response) => {
          if (response) return response;

          if (isOfflineShell) {
            return caches.match(event.request, { cacheName: RUNTIME_CACHE })
              .then((shell) => shell || caches.match('/offline.html'));
          }

          const accept = event.request.headers.get('accept') || '';
          if (accept.includes('text/html')) {
            return caches.match('/offline.html');
          }
        });
      })
  );
});
