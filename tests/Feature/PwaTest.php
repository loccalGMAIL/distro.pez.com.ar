<?php

use App\Models\CompanySetting;

test('the manifest is public and describes an installable standalone app', function () {
    $response = $this->get(route('pwa.manifest'));

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/manifest+json');

    $manifest = $response->json();

    expect($manifest)
        ->display->toBe('standalone')
        ->start_url->toBe('/dashboard')
        ->scope->toBe('/')
        ->id->toBe('/dashboard')
        ->lang->toBe('es-AR');
});

test('the manifest ships the icon sizes a browser needs to offer installing', function () {
    $icons = collect($this->get(route('pwa.manifest'))->json('icons'));

    expect($icons->pluck('sizes')->all())->toContain('192x192', '512x512');

    // Sin un ícono maskable Android recorta el cuadrado y se come el logo.
    expect($icons->where('purpose', 'maskable'))->not->toBeEmpty();

    $icons->each(function (array $icon) {
        $path = public_path(parse_url((string) $icon['src'], PHP_URL_PATH) ?: '');

        expect(file_exists($path))->toBeTrue("Falta el ícono {$icon['src']}");
    });
});

test('the manifest names the app after the company', function () {
    CompanySetting::factory()->create(['razon_social' => 'Distribuidora Pez']);

    expect($this->get(route('pwa.manifest'))->json('name'))->toBe('Distribuidora Pez');
});

test('the manifest falls back to the app name when there is no company loaded', function () {
    config(['app.name' => 'Distro']);

    expect($this->get(route('pwa.manifest'))->json('name'))->toBe('Distro');
});

test('the manifest shortcuts point at real urls inside the scope', function () {
    $shortcuts = collect($this->get(route('pwa.manifest'))->json('shortcuts'));

    expect($shortcuts)->toHaveCount(3);

    $shortcuts->each(function (array $shortcut) {
        expect(parse_url((string) $shortcut['url'], PHP_URL_PATH))->toStartWith('/');
    });
});

test('the service worker is served from the root so it can control the whole site', function () {
    $response = $this->get('/sw.js');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('javascript');
    // Sin esto el navegador puede quedarse con un sw.js viejo de su caché HTTP.
    expect($response->headers->get('Cache-Control'))->toContain('no-cache');
});

test('the service worker caches per version so a release invalidates the old cache', function () {
    config(['app.version' => '9.9.9']);

    expect($this->get('/sw.js')->getContent())->toContain('9.9.9');
});

test('the service worker never caches html or livewire', function () {
    $contents = (string) $this->get('/sw.js')->getContent();

    // El HTML del panel lleva el token CSRF adentro: servir una copia vieja
    // devuelve 419, y en un dispositivo compartido filtra la pantalla de otro
    // usuario. Si alguien agrega HTML a la caché, este test tiene que romper.
    expect($contents)
        ->not->toContain("'/livewire/'")
        ->toContain("request.mode === 'navigate'");

    expect($contents)->toContain('CACHEABLE_PREFIXES');
});

test('the offline page renders without a session or authentication', function () {
    $response = $this->get(route('pwa.offline'));

    $response->assertOk();
    $response->assertSee('Sin conexión');

    // Se cachea en el navegador: si viniera con Set-Cookie repondría una sesión
    // vieja cada vez que se sirve desde la caché y patearía al usuario al login.
    expect($response->headers->getCookies())->toBeEmpty();
});

test('the panel injects the manifest link and the service worker registration', function () {
    $response = $this->get('/dashboard/login');

    $response->assertOk();
    $response->assertSee(route('pwa.manifest'), escape: false);
    $response->assertSee('viewport-fit=cover', escape: false);
    $response->assertSee('serviceWorker', escape: false);
});
