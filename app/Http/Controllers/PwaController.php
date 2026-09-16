<?php

namespace App\Http\Controllers;

use App\Filament\Clusters\Catalog\Resources\Products\Pages\ListProducts;
use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Models\CompanySetting;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sirve las piezas de la PWA: el manifest, el service worker, la pantalla que
 * se muestra cuando una navegación falla sin conexión, y los splash screens
 * de iOS.
 *
 * @phpstan-type SplashScreen array{width: int, height: int, cssWidth: int, cssHeight: int, dpr: int}
 */
class PwaController extends Controller
{
    /**
     * El color del topbar de Filament en modo claro. Es el que pinta la barra
     * de estado de Android, así que tiene que coincidir con `.fi-topbar`
     * (`background-color: var(--color-white)`) y no con el color primario.
     */
    private const THEME_COLOR = '#ffffff';

    /** Fondo del splash screen que Android arma con el ícono. */
    private const BACKGROUND_COLOR = '#111111';

    /**
     * Mismo color que `BACKGROUND_COLOR`, ya descompuesto: GD pide cada canal
     * como `int<0,255>`, y `hexdec()` devuelve `int|float` sin ese rango, así
     * que decodificar el hex en runtime no tipa mejor que escribirlo una vez.
     *
     * @var array{int, int, int}
     */
    private const BACKGROUND_RGB = [17, 17, 17];

    /**
     * Splash screens de iOS por dispositivo, en portrait. Sin esto, abrir la
     * app instalada muestra una pantalla en blanco hasta que carga el panel:
     * iOS no usa `background_color` del manifest, necesita un PNG del tamaño
     * exacto del dispositivo declarado con `media` (ver `head.blade.php`).
     * `width`/`height` son píxeles físicos (los que pide la ruta); `cssWidth`/
     * `cssHeight`/`dpr` son los que identifican al dispositivo en el `media`.
     *
     * @var array<int, SplashScreen>
     */
    private const SPLASH_SCREENS = [
        ['width' => 750, 'height' => 1334, 'cssWidth' => 375, 'cssHeight' => 667, 'dpr' => 2],
        ['width' => 828, 'height' => 1792, 'cssWidth' => 414, 'cssHeight' => 896, 'dpr' => 2],
        ['width' => 1125, 'height' => 2436, 'cssWidth' => 375, 'cssHeight' => 812, 'dpr' => 3],
        ['width' => 1170, 'height' => 2532, 'cssWidth' => 390, 'cssHeight' => 844, 'dpr' => 3],
        ['width' => 1179, 'height' => 2556, 'cssWidth' => 393, 'cssHeight' => 852, 'dpr' => 3],
        ['width' => 1206, 'height' => 2622, 'cssWidth' => 402, 'cssHeight' => 874, 'dpr' => 3],
        ['width' => 1242, 'height' => 2208, 'cssWidth' => 414, 'cssHeight' => 736, 'dpr' => 3],
        ['width' => 1242, 'height' => 2688, 'cssWidth' => 414, 'cssHeight' => 896, 'dpr' => 3],
        ['width' => 1284, 'height' => 2778, 'cssWidth' => 428, 'cssHeight' => 926, 'dpr' => 3],
        ['width' => 1290, 'height' => 2796, 'cssWidth' => 430, 'cssHeight' => 932, 'dpr' => 3],
        ['width' => 1320, 'height' => 2868, 'cssWidth' => 440, 'cssHeight' => 956, 'dpr' => 3],
    ];

    public function manifest(): JsonResponse
    {
        $name = CompanySetting::appName();

        return response()
            ->json([
                // `id` fija la identidad de la instalación: si algún día cambia
                // `start_url`, el sistema sigue reconociendo la app instalada
                // en vez de ofrecer instalar una segunda. Por eso comparte la
                // misma fuente que `start_url` en vez de repetirla a mano.
                'id' => $this->dashboardPath(),
                'name' => $name,
                'short_name' => Str::limit($name, 12, ''),
                'description' => 'Gestión de ventas, compras, stock y fichajes.',
                'lang' => 'es-AR',
                'dir' => 'ltr',
                'start_url' => $this->dashboardPath(),
                // El scope arranca en la raíz y no en `/dashboard` para que los
                // PDF (comprobantes, listas de precios, recibos de fichaje) se
                // abran dentro de la app y no salten al navegador.
                'scope' => '/',
                'display' => 'standalone',
                'display_override' => ['standalone', 'minimal-ui'],
                'orientation' => 'any',
                'background_color' => self::BACKGROUND_COLOR,
                'theme_color' => self::THEME_COLOR,
                'icons' => $this->icons(),
                'shortcuts' => $this->shortcuts(),
                'screenshots' => $this->screenshots(),
            ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker(): Response
    {
        $contents = view('pwa.sw', [
            'version' => (string) config('app.version'),
            'offlineUrl' => route('pwa.offline', absolute: false),
        ])->render();

        return response($contents)
            ->header('Content-Type', 'text/javascript')
            // Sin esto el navegador puede servir un sw.js viejo desde su propia
            // caché HTTP y la app se queda clavada en la versión anterior.
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function offline(): View
    {
        return view('pwa.offline', [
            'name' => CompanySetting::appName(),
        ]);
    }

    /**
     * Expone la lista para que `head.blade.php` emita un `<link>` por
     * dispositivo sin duplicar los tamaños a mano en la vista.
     *
     * @return array<int, SplashScreen>
     */
    public static function splashScreens(): array
    {
        return self::SPLASH_SCREENS;
    }

    /**
     * Compone el PNG del splash a partir del ícono maskable. `$width`/`$height`
     * vienen de la ruta como píxeles físicos; se validan contra
     * `SPLASH_SCREENS` porque generar un canvas del tamaño que pida cualquiera
     * es un DoS de memoria servido gratis.
     */
    public function splash(int $width, int $height): Response
    {
        $isKnownScreen = collect(self::SPLASH_SCREENS)
            ->contains(fn (array $screen): bool => $screen['width'] === $width && $screen['height'] === $height);

        // `$width`/`$height` solo llegan acá dentro de lo que ya listamos en
        // SPLASH_SCREENS (todo > 0), pero eso no lo sabe el analizador de
        // tipos: la guarda de abajo es la que le prueba a GD que son positivos.
        if (! $isKnownScreen || $width < 1 || $height < 1) {
            abort(404);
        }

        return response($this->renderSplash($width, $height))
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=604800');
    }

    private function renderSplash(int $width, int $height): string
    {
        [$red, $green, $blue] = self::BACKGROUND_RGB;

        // `splash()` ya rechazó cualquier par que no esté en SPLASH_SCREENS
        // (siempre > 0); este `max(1, ...)` es solo lo que le prueba al
        // analizador de tipos que el canvas nunca es 0x0.
        $canvas = imagecreatetruecolor(max(1, $width), max(1, $height));

        if ($canvas === false) {
            throw new RuntimeException('No se pudo crear el canvas del splash.');
        }

        $background = imagecolorallocate($canvas, $red, $green, $blue);

        if ($background === false) {
            throw new RuntimeException('No se pudo asignar el color de fondo del splash.');
        }

        imagefill($canvas, 0, 0, $background);

        // El maskable ya lleva el mismo BACKGROUND_COLOR de fondo relleno
        // hasta el borde, así que pegarlo escalado no deja ningún borde
        // visible contra el canvas: parece un solo fondo con el logo adentro.
        $logo = imagecreatefrompng(public_path('icons/icon-512-maskable.png'));

        if ($logo === false) {
            throw new RuntimeException('icon-512-maskable.png no es un PNG válido.');
        }

        $logoSize = max(1, intdiv(min($width, $height), 3));

        imagecopyresampled(
            $canvas,
            $logo,
            intdiv($width - $logoSize, 2),
            intdiv($height - $logoSize, 2),
            0,
            0,
            $logoSize,
            $logoSize,
            imagesx($logo),
            imagesy($logo),
        );

        imagedestroy($logo);

        ob_start();
        imagepng($canvas);
        $contents = (string) ob_get_clean();

        imagedestroy($canvas);

        return $contents;
    }

    /**
     * @return array<int, array{src: string, sizes: string, type: string, purpose: string}>
     */
    private function icons(): array
    {
        return [
            [
                'src' => $this->path(asset('icons/icon-192.png')),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $this->path(asset('icons/icon-512.png')),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            // `maskable` es un archivo aparte a propósito: lleva el fondo
            // cuadrado completo para que el sistema pueda recortarlo al contorno
            // que use (círculo, squircle, gota) sin comerse el logo.
            [
                'src' => $this->path(asset('icons/icon-512-maskable.png')),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }

    /**
     * Sin esto Chrome en Android muestra el mini-infobar en vez del diálogo de
     * instalación rico con vista previa. `narrow` es lo que ve un celular al
     * instalar; `wide` es lo que ve desktop/tablet.
     *
     * @return array<int, array{src: string, sizes: string, type: string, form_factor: string, label: string}>
     */
    private function screenshots(): array
    {
        return [
            [
                'src' => $this->path(asset('icons/screenshot-dashboard-narrow.png')),
                'sizes' => '500x603',
                'type' => 'image/png',
                'form_factor' => 'narrow',
                'label' => 'Dashboard con accesos rápidos a ventas, compras y fichaje',
            ],
            [
                'src' => $this->path(asset('icons/screenshot-nueva-venta-narrow.png')),
                'sizes' => '500x603',
                'type' => 'image/png',
                'form_factor' => 'narrow',
                'label' => 'Alta de venta con el catálogo de productos',
            ],
            [
                'src' => $this->path(asset('icons/screenshot-dashboard.png')),
                'sizes' => '1366x594',
                'type' => 'image/png',
                'form_factor' => 'wide',
                'label' => 'Dashboard con accesos rápidos a ventas, compras y fichaje',
            ],
            [
                'src' => $this->path(asset('icons/screenshot-nueva-venta.png')),
                'sizes' => '1366x594',
                'type' => 'image/png',
                'form_factor' => 'wide',
                'label' => 'Alta de venta con el catálogo de productos',
            ],
        ];
    }

    /**
     * Accesos directos del menú contextual del ícono (mantener apretado).
     *
     * Las URLs se piden a Filament en vez de escribirlas a mano porque los
     * slugs los arma el panel a partir del cluster y del resource.
     *
     * @return array<int, array{name: string, short_name: string, url: string, icons: array<int, array{src: string, sizes: string, type: string}>}>
     */
    private function shortcuts(): array
    {
        return [
            $this->shortcut('Nueva venta', 'Vender', SaleResource::getUrl('create')),
            $this->shortcut('Escanear factura', 'Escanear', ScanPurchase::getUrl()),
            $this->shortcut('Productos', 'Productos', ListProducts::getUrl()),
        ];
    }

    /**
     * Las URLs del manifest se guardan como path y no absolutas: el navegador
     * las resuelve contra la URL del propio manifest, así que la app instalada
     * sigue funcionando aunque el dominio cambie.
     */
    private function path(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    private function dashboardPath(): string
    {
        return $this->path(Filament::getPanel('dashboard')->getUrl() ?? '/dashboard');
    }

    /**
     * @return array{name: string, short_name: string, url: string, icons: array<int, array{src: string, sizes: string, type: string}>}
     */
    private function shortcut(string $name, string $shortName, string $url): array
    {
        return [
            'name' => $name,
            'short_name' => $shortName,
            'url' => $this->path($url),
            'icons' => [
                [
                    'src' => $this->path(asset('icons/icon-192.png')),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
            ],
        ];
    }
}
