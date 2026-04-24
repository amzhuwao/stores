const CACHE_VERSION = 'stores-pwa-v2';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;
const APP_BASE_PATH = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const OFFLINE_URL = `${APP_BASE_PATH}/public/offline.html`;

const STATIC_ASSETS = [
  `${APP_BASE_PATH}/`,
  `${APP_BASE_PATH}/dashboard.php`,
  `${APP_BASE_PATH}/login.php`,
  `${APP_BASE_PATH}/pages/forgot-password.php`,
  `${APP_BASE_PATH}/pages/reset-password.php`,
  `${APP_BASE_PATH}/pages/reports/index.php`,
  `${APP_BASE_PATH}/pages/requisition/index.php`,
  `${APP_BASE_PATH}/pages/stock-issues/index.php`,
  `${APP_BASE_PATH}/pages/grn/index.php`,
  `${APP_BASE_PATH}/pages/adjustments/index.php`,
  `${APP_BASE_PATH}/pages/stock/view.php`,
  `${APP_BASE_PATH}/pages/profile.php`,
  `${APP_BASE_PATH}/pages/change-password.php`,
  `${APP_BASE_PATH}/public/manifest.php`,
  `${APP_BASE_PATH}/public/offline.html`,
  `${APP_BASE_PATH}/public/css/style.css`,
  `${APP_BASE_PATH}/public/js/main.js`,
  `${APP_BASE_PATH}/public/js/pwa.js`,
  `${APP_BASE_PATH}/public/img/pwa-icon-192.svg`,
  `${APP_BASE_PATH}/public/img/pwa-icon-512.svg`
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
      fetch(event.request, { cache: 'no-store' })
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

        return fetch(event.request, { cache: 'no-store' }).then(networkResponse => {
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
    fetch(event.request, { cache: 'no-store' }).then(networkResponse => {
      if (networkResponse && networkResponse.status === 200) {
        const responseClone = networkResponse.clone();
        caches.open(RUNTIME_CACHE).then(cache => cache.put(event.request, responseClone));
      }

      return networkResponse;
    }).catch(() => caches.match(event.request))
  );
});