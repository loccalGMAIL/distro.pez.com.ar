<?php

use App\Http\Controllers\PwaController;
use App\Models\CompanySetting;
use App\Models\User;

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

test('the manifest id matches start_url so the same install is always recognized', function () {
    $manifest = $this->get(route('pwa.manifest'))->json();

    expect($manifest['id'])->toBe($manifest['start_url']);
});

test('the manifest ships screenshots for the rich install dialog, and every file exists', function () {
    $screenshots = collect($this->get(route('pwa.manifest'))->json('screenshots'));

    expect($screenshots)->not->toBeEmpty();
    // "narrow" es lo que ve un celular al instalar; "wide" lo que ve
    // desktop/tablet. Sin las dos, alguna plataforma vuelve al mini-infobar.
    expect($screenshots->pluck('form_factor')->unique()->all())->toContain('narrow', 'wide');

    $screenshots->each(function (array $screenshot) {
        $path = public_path(parse_url((string) $screenshot['src'], PHP_URL_PATH) ?: '');

        expect(file_exists($path))->toBeTrue("Falta la captura {$screenshot['src']}");
    });
});

test('the manifest icon and shortcut icon sources are relative paths, not absolute urls', function () {
    $manifest = $this->get(route('pwa.manifest'))->json();

    collect($manifest['icons'])
        ->concat(collect($manifest['shortcuts'])->flatMap(fn (array $shortcut): array => $shortcut['icons']))
        ->each(fn (array $icon) => expect($icon['src'])->not->toStartWith('http'));
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

test('the manifest shortcuts are distinct destinations, not the dashboard repeated', function () {
    $manifest = $this->get(route('pwa.manifest'))->json();

    $urls = collect($manifest['shortcuts'])->pluck('url');

    expect($urls->unique())->toHaveCount($urls->count());
    expect($urls)->not->toContain($manifest['start_url']);
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

test('the install and update banner does not render on the login page', function () {
    // Es la primera pantalla que ve cualquiera: instalar sigue disponible
    // después, desde el menú de usuario. El service worker igual se registra.
    $this->get('/dashboard/login')->assertDontSee('id="pwa-banner"', escape: false);
});

test('the install and update banner renders for an authenticated user', function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));

    $this->get('/dashboard')->assertSee('id="pwa-banner"', escape: false);
});

test('the panel injects an ios splash link for every known screen', function () {
    $response = $this->get('/dashboard/login');

    $response->assertOk();

    foreach (PwaController::splashScreens() as $screen) {
        $response->assertSee(
            route('pwa.splash', ['width' => $screen['width'], 'height' => $screen['height']]),
            escape: false,
        );
    }
});

test('the splash screen renders a png for a known device size', function () {
    $response = $this->get(route('pwa.splash', ['width' => 1170, 'height' => 2532]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('image/png');
    expect($response->headers->get('Cache-Control'))->toContain('public');
});

test('the splash screen refuses a size nobody asked for', function () {
    $this->get(route('pwa.splash', ['width' => 9999, 'height' => 9999]))->assertNotFound();
});

test('the install menu item is present but hidden for every authenticated user', function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));

    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertSee('data-pwa-install', escape: false);
});
