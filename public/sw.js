// SiniKajian Service Worker - Version 15.0 (Laravel PWA)
const CACHE_NAME = 'sinikajian-v15.0';
const PREFETCH_CACHE = 'sinikajian-prefetch-v1';
const PREFETCH_TTL_MS = 15000;

// Cap waktu aksi mutasi terakhir (POST/PUT/DELETE dari halaman mana pun).
// Entri prefetch yang dibuat SEBELUM mutasi tidak boleh disajikan —
// pelajaran mahal 2026-10-20: data basi yang tampil lagi adalah sumber
// utama keluhan user. Prefetch hanya menang bila benar-benar segar.
let lastMutationAt = 0;

const STATIC_ASSETS = [
  '/',
  '/manifest.json',
  '/css/app.css',
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('[SW] Pre-caching static assets non-blocking warning:', err);
      });
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[SW] Purging outdated cache store:', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => caches.delete(PREFETCH_CACHE))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  // Mutasi apa pun membatalkan kelayakan semua prefetch yang lebih lama.
  if (event.request.method !== 'GET') {
    lastMutationAt = Date.now();
    return;
  }

  const url = new URL(event.request.url);

  // 0. Prefetch intake: halaman meminta halaman lain dimuat lebih dulu
  //    (header X-Prefetch). Respons disimpan bertanda waktu di cache
  //    khusus; navigasi asli yang menyusul boleh memakainya bila segar.
  if (event.request.headers.get('X-Prefetch') === '1' && url.origin === self.location.origin && url.search === '') {
    event.respondWith(
      fetch(event.request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200 && !networkResponse.headers.get('set-cookie')) {
          const headers = new Headers(networkResponse.headers);
          headers.set('X-Prefetch-At', String(Date.now()));
          const stamped = new Response(networkResponse.clone().body, {
            status: networkResponse.status,
            statusText: networkResponse.statusText,
            headers,
          });
          caches.open(PREFETCH_CACHE).then((cache) => cache.put(event.request.url, stamped));
        }
        return networkResponse;
      }).catch(() => new Response('', { status: 504 }))
    );
    return;
  }

  // 1. Navigation / HTML Document requests: Prefetch segar -> Network-First
  const isDocument = event.request.mode === 'navigate' ||
                     event.request.destination === 'document' ||
                     url.pathname === '/' ||
                     url.pathname.endsWith('.html');

  if (isDocument) {
    event.respondWith((async () => {
      if (event.request.mode === 'navigate' && url.search === '') {
        try {
          const cache = await caches.open(PREFETCH_CACHE);
          const hit = await cache.match(event.request.url);
          if (hit) {
            await cache.delete(event.request.url); // sekali pakai
            const at = Number(hit.headers.get('X-Prefetch-At') || 0);
            if (Date.now() - at < PREFETCH_TTL_MS && at > lastMutationAt) {
              return hit;
            }
          }
        } catch (e) { /* abaikan, lanjut jaringan */ }
      }
      try {
        const networkResponse = await fetch(event.request, { cache: 'no-store' });
        if (networkResponse && networkResponse.status === 200) {
          const clone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, clone);
          });
        }
        return networkResponse;
      } catch (e) {
        const cached = await caches.match(event.request);
        return cached || caches.match('/');
      }
    })());
    return;
  }

  // 2. Static Assets: Stale-While-Revalidate
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      const fetchPromise = fetch(event.request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          const clone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, clone);
          });
        }
        return networkResponse;
      }).catch(() => cachedResponse);

      return cachedResponse || fetchPromise;
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data === 'PURGE_CACHE') {
    caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k))));
  }
});

// Web Push Notifications
self.addEventListener('push', (event) => {
  let data = { title: 'Majelisku', body: 'Ada kajian baru!' };
  try {
    if (event.data) data = event.data.json();
  } catch (e) {}

  event.waitUntil(
    self.registration.showNotification(data.title || 'Majelisku', {
      body: data.body || '',
      icon: '/icon-192-v2.png',
      badge: '/icon-192-v2.png',
      data: { url: data.url || '/' },
      vibrate: [200, 100, 200],
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) return client.focus();
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
