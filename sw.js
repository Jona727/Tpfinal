const CACHE_NAME = 'solufeed-v1';
const ASSETS_TO_CACHE = [
  '/solufeed/admin/campo/index.php',
  '/solufeed/admin/alimentaciones/registrar.php',
  '/solufeed/admin/pesadas/registrar.php',
  '/solufeed/assets/css/main.css',
  '/solufeed/assets/js/offline_manager.js',
  'https://cdn.jsdelivr.net/npm/chart.js',
  'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap'
];

// Instalación: Cachear recursos estáticos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('✅ Service Worker: Caching assets');
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// Activación: Limpiar caches viejas
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('🧹 Service Worker: Removing old cache', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
});

// Fetch: Servir desde caché o red
self.addEventListener('fetch', (event) => {
  // Solo interceptar peticiones GET
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((response) => {
      // Si está en caché, devolverlo
      if (response) {
        return response;
      }

      // Si no, ir a la red
      return fetch(event.request).catch(() => {
        // Fallback offline (opcional, por ahora solo retornamos nada si falla)
        // Podríamos retornar una página "offline.html" genérica aquí
      });
    })
  );
});
