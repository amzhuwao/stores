const CACHE_VERSION = 'stores-pwa-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;
const OFFLINE_URL = '/stores/public/offline.html';

const STATIC_ASSETS = [
  '/stores/',
  '/stores/dashboard.php',
  '/stores/login.php',
  '/stores/public/manifest.json',
  '/stores/public/offline.html',
  '/stores/public/css/style.css',
  '/stores/public/js/main.js',
  '/stores/public/js/pwa.js',
  '/stores/public/img/pwa-icon-192.svg',
  '/stores/public/img/pwa-icon-512.svg'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys
        .filter(key => key.startsWith('stores-pwa-') && key !== STATIC_CACHE && key !== RUNTIME_CACHE)
        .map(key => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  const requestUrl = new URL(event.request.url);
  const isNavigation = event.request.mode === 'navigate';
  const isSameOrigin = requestUrl.origin === self.location.origin;

  if (isNavigation) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const responseClone = response.clone();
          caches.open(RUNTIME_CACHE).then(cache => cache.put(event.request, responseClone));
          return response;
        })
        .catch(async () => {
          const cachedPage = await caches.match(event.request);
          if (cachedPage) {
            return cachedPage;
          }

          const offlinePage = await caches.match(OFFLINE_URL);
          return offlinePage || Response.error();
        })
    );
    return;
  }

  if (isSameOrigin) {
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }

        return fetch(event.request).then(networkResponse => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(RUNTIME_CACHE).then(cache => cache.put(event.request, responseClone));
          }

          return networkResponse;
        });
      })
    );
    return;
  }

  event.respondWith(
    fetch(event.request).then(networkResponse => {
      if (networkResponse && networkResponse.status === 200) {
        const responseClone = networkResponse.clone();
        caches.open(RUNTIME_CACHE).then(cache => cache.put(event.request, responseClone));
      }

      return networkResponse;
    }).catch(() => caches.match(event.request))
  );
});