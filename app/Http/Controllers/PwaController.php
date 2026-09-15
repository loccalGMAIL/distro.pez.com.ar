<?php

namespace App\Http\Controllers;

use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Models\CompanySetting;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Sirve las tres piezas de la PWA: el manifest, el service worker y la
 * pantalla que se muestra cuando una navegación falla sin conexión.
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

    public function manifest(): JsonResponse
    {
        $name = CompanySetting::appName();

        return response()
            ->json([
                // `id` fija la identidad de la instalación: si algún día cambia
                // `start_url`, el sistema sigue reconociendo la app instalada
                // en vez de ofrecer instalar una segunda.
                'id' => '/dashboard',
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
     * @return array<int, array{src: string, sizes: string, type: string, purpose: string}>
     */
    private function icons(): array
    {
        return [
            [
                'src' => asset('icons/icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icons/icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            // `maskable` es un archivo aparte a propósito: lleva el fondo
            // cuadrado completo para que el sistema pueda recortarlo al contorno
            // que use (círculo, squircle, gota) sin comerse el logo.
            [
                'src' => asset('icons/icon-512-maskable.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
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
            $this->shortcut('Fichaje', 'Fichaje', $this->dashboardPath()),
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
                    'src' => asset('icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
            ],
        ];
    }
}
