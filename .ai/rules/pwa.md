---
paths:
  - 'app/Http/Controllers/PwaController.php'
  - 'resources/views/pwa/**'
  - 'resources/views/filament/pwa/**'
  - 'public/icons/**'
  - 'resources/css/filament/dashboard/theme.css'
---

# PWA

## El service worker no cachea HTML ni Livewire, y no es negociable
`resources/views/pwa/sw.blade.php` solo guarda en caché assets con la versión en
la URL (`/build/`, `/css/`, `/js/`, `/fonts/`, `/icons/`, más favicons) y la
pantalla `/offline`. Las navegaciones van siempre a la red; si falla se devuelve
la pantalla offline, pero la respuesta **nunca** se guarda.

Si alguna vez tenés la tentación de cachear el HTML del panel, estas son las tres
formas en que rompe, todas juntas:

1. El HTML lleva el token CSRF adentro. Una copia vieja devuelve 419 en cada
   interacción de Livewire, y el usuario no tiene forma de salir salvo limpiando
   la caché del navegador a mano.
2. El dispositivo del mostrador es compartido. La pantalla cacheada de un usuario
   se le muestra al siguiente, con sus datos.
3. `wire:navigate` navega por `fetch`, no con una navegación del navegador. Un
   service worker que intercepta documentos desincroniza la navegación SPA.

`/livewire/` está deliberadamente fuera de `CACHEABLE_PREFIXES` por el mismo
motivo: no se gana casi nada y cualquier desfasaje entre el JS de Livewire
cacheado y el servidor rompe el panel entero. `tests/Feature/PwaTest.php` tiene
un test que falla si alguien agrega HTML o Livewire a la caché.

## Las rutas de la PWA van sin sesión a propósito
Las tres (`pwa.manifest`, `pwa.service-worker`, `pwa.offline`) están en un grupo
`Route::withoutMiddleware([StartSession::class, ShareErrorsFromSession::class])`.
No es una optimización: la pantalla `/offline` se guarda en la caché del
navegador, y si esa respuesta llevara un `Set-Cookie` de sesión, cada vez que se
sirve desde la caché repondría una sesión vieja y patearía al usuario al login.
El test verifica que la respuesta no traiga cookies.

## El nombre de la caché lleva la versión, así que hay que bumpear `APP_VERSION`
`sw.blade.php` arma `CACHE_NAME` con `config('app.version')` y en `activate`
borra cualquier caché `distro-v*` que no sea la actual. O sea: **un deploy que no
bumpee la versión no invalida la caché de assets**. Como el proyecto ya bumpea
versión por release (decisión 0002), alcanza con seguir esa regla.

El `sw.js` se sirve por ruta y no como archivo estático justamente para poder
inyectar esa versión, y va con `Cache-Control: no-cache` porque si no el
navegador puede servir un `sw.js` viejo de su propia caché HTTP y la app queda
clavada en la versión anterior.

## `viewport-fit=cover` vive en el hook `HEAD_END` y pisa el de Filament
`resources/views/filament/pwa/head.blade.php` emite su propio
`<meta name="viewport">`. Es a propósito: Filament emite
`width=device-width, initial-scale=1` sin `viewport-fit=cover`, y `HEAD_END`
corre después, así que el de acá gana. Sin ese `viewport-fit=cover`,
`env(safe-area-inset-*)` devuelve 0 en iOS y las reglas de safe area de
`theme.css` (bloque `@media (display-mode: standalone)`) no hacen nada.

El `apple-mobile-web-app-status-bar-style` es `default` y no
`black-translucent`: este último mete el contenido debajo de la barra de estado y
fuerza texto blanco, que sobre el topbar blanco del modo claro queda invisible.

## El banner se muestra por `style.display`, no por `hidden` ni por `.hidden`
En `resources/views/filament/pwa/scripts.blade.php`, tanto el atributo `[hidden]`
como la utilidad `.hidden` de Tailwind compiten en especificidad con el `display`
que traen las clases del propio elemento (`flex`, `inline-flex`), y cuál gana
depende del orden en que Tailwind emita las utilidades, no del estado real. Es el
mismo problema que el `contents!` con important del grid de productos de
`SaleForm` (ver `.ai/rules/sales.md`). Acá se resuelve manipulando
`element.style.display`, que no compite con nada.

Ese script corre **en cada navegación** de `wire:navigate` (el body se vuelve a
renderizar, así que el markup del banner es nuevo y hay que volver a decidir qué
mostrar), pero todo lo que se registra una sola vez —listeners delegados en
`document`, que sí sobrevive a `wire:navigate`, y el registro del service
worker— está detrás del guard `window.pwaBooted`.

## El banner no se renderiza en el login, a propósito
`scripts.blade.php` envuelve el `<div id="pwa-banner">` en
`@unless (request()->routeIs('filament.dashboard.auth.login'))`: es la primera
pantalla que ve cualquiera, todavía no sabe si va a volver a usar la app. El
registro del service worker sigue corriendo igual ahí (así los assets quedan en
caché desde la primera visita) — lo que se saca es solo el markup del banner.
Como `banner()` devuelve `null` cuando el `div` no existe, `showBanner()` y
`hideBanner()` ya hacían early-return sin este caso en mente, así que no hizo
falta tocar la lógica de JS, solo el Blade. Instalar sigue disponible después
de loguearse, desde `filament.pwa.user-menu-item`.

## Cambiar CSS de esta rama obliga a `npm run build`
`public/build/` está commiteado (el deploy no corre Vite). Las clases de Tailwind
nuevas que se usen en `resources/views/filament/pwa/**` y las reglas de safe area
de `theme.css` no existen en el CSS compilado hasta que alguien corra
`npm run build` y commitee el resultado.

## El ítem "Instalar app" del menú de usuario no depende de Shield
`filament.pwa.user-menu-item` entra por `USER_MENU_PROFILE_AFTER`, no por una
página de Configuración: el cluster Configuración está detrás de `View:General`
(solo `admin`/`Dueño`), y los que instalan la app desde el celular son
`vendedor`/`deposito`/`chofer`. Arranca oculto con `style.display = 'none'` (la
misma regla de especificidad que el banner) y `scripts.blade.php` lo sincroniza
en cada `refreshPwaUi()` — que corre en cada navegación de `wire:navigate`,
porque `USER_MENU_PROFILE_AFTER` también se vuelve a renderizar. Usa
`querySelectorAll`, no `getElementById`: el dropdown del menú de usuario se
teletransporta (`:teleport`) al final del `<body>`.

Instalar por acá **olvida** el descarte de `pwa-install-dismissed`: si el
usuario entró por el menú es porque quiere verlo, así que dejar el flag puesto
le ocultaría el paso a paso de iOS después de cerrarlo una vez.

Depende de que el panel tenga `->profile()`: sin eso Filament no abre el
`<x-filament::dropdown.list>` en el que cuelga `USER_MENU_PROFILE_AFTER`, y el
hook no se renderiza (sin error, el ítem simplemente no aparece).

## `clients.claim()` en la primera instalación no es una actualización
`activate` llama `clients.claim()` siempre, también en la primerísima
instalación del service worker — y eso dispara `controllerchange` igual que
una versión nueva. Sin distinguir los dos casos, todo visitante nuevo se come
un `location.reload()` espurio. La distinción es `hadController =
!!navigator.serviceWorker.controller` leído ANTES de `register()`: si no había
`controller` previo, no hay nada viejo en pantalla que recargar.

## `cache.add(OFFLINE_URL)` en `install` lleva `.catch(() => {})`
Si la red falla justo en el instante del `install` (intermitencia, un 500
pasajero), no cachear `/offline` no puede tirar abajo el service worker
entero: se perdería con él el cache-first de `CACHEABLE_PREFIXES`, que no
depende de la pantalla offline. Sin la app instalada larga, `registration.update()`
nunca corre solo: se fuerza a mano en `visibilitychange` (vuelta a foreground) y
cada 30 minutos, porque el chequeo automático del navegador es por navegación o
cada 24h y una PWA abierta días en el mostrador no navega nunca.

## Los splash screens de iOS se generan con GD, no se committean
`PwaController::splash()` compone el PNG en runtime a partir de
`icon-512-maskable.png` (ya lleva `BACKGROUND_COLOR` de fondo hasta el borde,
por eso pegarlo escalado no deja bordes visibles) en vez de guardar ~11
binarios por tamaño de iPhone. La ruta valida el par `{width}x{height}` contra
`SPLASH_SCREENS` y devuelve 404 si no matchea — no es opcional, es lo que evita
que cualquiera pida un canvas gigante y lo vuelva un DoS de memoria. Vive bajo
`/icons/splash/` a propósito: ese prefijo ya está en `CACHEABLE_PREFIXES`.

## Las `screenshots` del manifest sí son binarios reales, a propósito
A diferencia de los íconos y los splash (generados), `public/icons/screenshot-*.png`
son capturas reales de la app (tomadas con datos ya cargados: no hay nada que
generar en runtime para esto). Hay dos pares — `narrow` (mobile, lo que ve un
celular al instalar) y `wide` (desktop/tablet) — de las mismas dos pantallas
(Dashboard y Crear Venta). Sin las dos variantes de `form_factor`, alguna
plataforma vuelve al mini-infobar en vez del diálogo de instalación con vista
previa. Cada `sizes` en el manifest tiene que matchear el tamaño real del
archivo, o Chrome descarta esa entrada en silencio.
