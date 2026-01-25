/**
 * SOLUFEED - Service Worker (v3.0)
 * (v3.0 - Arreglo de error crítico 'Failed to convert to Response')
 */

const CACHE_NAME = 'solufeed-cache-v3.2';

self.addEventListener('install', (e) => {
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.map(key => {
        if (key !== CACHE_NAME) return caches.delete(key);
      })
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = event.request.url;
  if (!url.startsWith('http')) return;

  event.respondWith(
    fetch(event.request)
      .then((res) => {
        if (res && res.status === 200) {
          const resClone = res.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, resClone);
          });
        }
        return res;
      })
      .catch(async () => {
        // MODO OFFLINE: Intentar recuperar de cache
        const exactMatch = await caches.match(event.request);
        if (exactMatch) return exactMatch;

        const flexibleMatch = await caches.match(event.request, { ignoreSearch: true });
        if (flexibleMatch) return flexibleMatch;

        // FALLBACK FINAL: Siempre retornar un Response válido para evitar errores del SW
        if (event.request.mode === 'navigate') {
          const offlinePage = await caches.match('/offline.html');
          if (offlinePage) return offlinePage;
        }

        // Si no es navegación (CSS, JS, Imágenes), retornar una respuesta vacía/error
        return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
      })
  );
});
