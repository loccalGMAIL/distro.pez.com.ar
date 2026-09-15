{{--
    Service worker de la app. Se sirve por `PwaController::serviceWorker()` y no
    como archivo estático para poder inyectarle la versión: el nombre de la
    caché la incluye, así que cada release invalida sola la anterior.

    REGLA QUE NO SE PUEDE ROMPER: acá NUNCA se cachea HTML ni nada de Livewire.
    El HTML del panel lleva el token CSRF adentro; servir una copia vieja
    devuelve 419 en cada interacción, y en un dispositivo compartido le
    mostraría a un usuario la pantalla del anterior. Lo único que se guarda son
    assets estáticos versionados y la pantalla de sin conexión.
--}}
const VERSION = @json($version);
const CACHE_NAME = `distro-v${VERSION}`;
const OFFLINE_URL = @json($offlineUrl);

/**
 * Prefijos de assets servidos con hash o con `?v=` en la URL. Al estar
 * versionados en la propia URL, cache-first nunca sirve algo desactivado: un
 * deploy cambia la URL y eso es un miss.
 *
 * `/livewire/` queda deliberadamente afuera. No se gana casi nada y cualquier
 * desincronización entre el JS de Livewire cacheado y el servidor rompe el
 * panel entero.
 */
const CACHEABLE_PREFIXES = ['/build/', '/css/', '/js/', '/fonts/', '/icons/'];
const CACHEABLE_PATHS = ['/favicon.ico', '/favicon.svg', '/apple-touch-icon.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        if (self.registration.navigationPreload) {
            await self.registration.navigationPreload.enable();
        }

        const names = await caches.keys();

        await Promise.all(
            names
                .filter((name) => name.startsWith('distro-v') && name !== CACHE_NAME)
                .map((name) => caches.delete(name)),
        );

        await self.clients.claim();
    })());
});

{{-- El SW nuevo espera: activa recién cuando el usuario acepta el aviso. --}}
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

const isCacheableAsset = (url) =>
    CACHEABLE_PREFIXES.some((prefix) => url.pathname.startsWith(prefix))
    || CACHEABLE_PATHS.includes(url.pathname);

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    /**
     * Navegaciones: siempre a la red, y si falla se muestra la pantalla de sin
     * conexión. La respuesta NO se guarda nunca (ver la regla de arriba).
     *
     * `wire:navigate` no entra por acá: navega con fetch, así que su `mode` no
     * es 'navigate' y cae en el passthrough del final.
     */
    if (request.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const preloaded = await event.preloadResponse;

                return preloaded || await fetch(request);
            } catch (error) {
                const cache = await caches.open(CACHE_NAME);
                const offline = await cache.match(OFFLINE_URL);

                return offline || Response.error();
            }
        })());

        return;
    }

    if (! isCacheableAsset(url)) {
        return;
    }

    event.respondWith((async () => {
        const cache = await caches.open(CACHE_NAME);
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        const response = await fetch(request);

        if (response.ok && response.type === 'basic') {
            cache.put(request, response.clone());
        }

        return response;
    })());
});
