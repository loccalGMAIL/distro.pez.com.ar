{{--
    Metadatos de la PWA. Se inyecta con `PanelsRenderHook::HEAD_END`, que corre
    DESPUÉS del `<meta name="viewport">` propio de Filament — por eso el viewport
    de acá lo pisa y suma `viewport-fit=cover`, sin el cual `env(safe-area-inset-*)`
    devuelve 0 en iOS y el contenido queda debajo de la barra de gestos.
--}}
@php
    $appName = \App\Models\CompanySetting::appName();
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
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

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
