{{--
    Metadatos de la PWA. Se inyecta con `PanelsRenderHook::HEAD_END`, que corre
    DESPUÉS del `<meta name="viewport">` propio de Filament — por eso el viewport
    de acá lo pisa y suma `viewport-fit=cover`, sin el cual `env(safe-area-inset-*)`
    devuelve 0 en iOS y el contenido queda debajo de la barra de gestos.
--}}
@php
    $appName = \App\Models\CompanySetting::appName();
    $splashScreens = \App\Http\Controllers\PwaController::splashScreens();
@endphp

<link rel="manifest" href="{{ route('pwa.manifest') }}">

<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

{{-- Pinta la barra de estado de Android al tono del topbar en cada esquema. --}}
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">

<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
{{--
    `default` y no `black-translucent`: este último mete el contenido debajo de
    la barra de estado y fuerza texto blanco, que sobre el topbar blanco del modo
    claro queda invisible.
--}}
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit($appName, 12, '') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

{{--
    Sin esto, abrir la app instalada en iPhone muestra una pantalla en blanco
    hasta que carga el panel: iOS no usa `background_color` del manifest para
    el splash, necesita un PNG del tamaño exacto del dispositivo. Solo
    portrait: en landscape ningún `media` matchea y queda el comportamiento de
    siempre, ni mejor ni peor.
--}}
@foreach ($splashScreens as $screen)
    <link
        rel="apple-touch-startup-image"
        href="{{ route('pwa.splash', ['width' => $screen['width'], 'height' => $screen['height']]) }}"
        media="(device-width: {{ $screen['cssWidth'] }}px) and (device-height: {{ $screen['cssHeight'] }}px) and (-webkit-device-pixel-ratio: {{ $screen['dpr'] }}) and (orientation: portrait)"
    >
@endforeach

<script>
    /**
     * Chrome dispara `beforeinstallprompt` apenas verifica que la app es
     * instalable, que puede ser antes de que corra cualquier script del final
     * del body. Por eso se captura acá, en el head, y el banner lo consume
     * después desde `window.deferredInstallPrompt`.
     */
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.deferredInstallPrompt = event;
        window.dispatchEvent(new CustomEvent('pwa:installable'));
    });
</script>
