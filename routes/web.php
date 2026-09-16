<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\PwaController;
use App\Models\PriceList;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\TimeEntrySettlement;
use App\Services\TimeEntryReport;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::redirect('/', '/dashboard/login')->name('home');

Route::get('/ventas/{sale}/comprobante', function (Sale $sale) {
    $comprobanteNumero = $sale->comprobanteNumero();

    abort_unless(filled($comprobanteNumero), 404);

    return $sale->comprobantePdf()->stream("comprobante-{$comprobanteNumero}.pdf");
})->middleware('auth')->name('sales.comprobante');

Route::get('/listas-precios/{priceList}/pdf', function (PriceList $priceList) {
    abort_unless($priceList->compartible, 404);

    return $priceList->productsPdf()->stream('lista-precios-'.Str::slug($priceList->nombre).'.pdf');
})->middleware('auth')->name('price-lists.pdf');

Route::get('/compras/{purchase}/archivo', function (Purchase $purchase) {
    abort_unless(filled($purchase->archivo_path), 404);
    abort_unless(Storage::disk('local')->exists($purchase->archivo_path), 404);

    return Storage::disk('local')->response($purchase->archivo_path);
})->middleware('auth')->name('purchases.archivo');

Route::get('/fichajes/reporte.pdf', function (Request $request) {
    abort_unless(Auth::user()?->can('View:TimeEntriesReport'), 403);

    $report = new TimeEntryReport(
        $request->integer('user_id') ?: null,
        $request->string('desde')->toString() ?: null,
        $request->string('hasta')->toString() ?: null,
        $request->string('liquidacion')->toString() ?: null,
    );

    return $report->pdf()->stream('reporte-fichajes.pdf');
})->middleware('auth')->name('time-entries.report.pdf');

Route::get('/fichajes/liquidaciones/{settlement}/recibo.pdf', function (TimeEntrySettlement $settlement) {
    abort_unless(Auth::user()?->can('View:TimeEntrySettlement'), 403);

    return $settlement->pdf()->stream("recibo-{$settlement->numero()}.pdf");
})->middleware('auth')->name('time-entry-settlements.receipt.pdf');

/*
 * Rutas de la PWA, deliberadamente sin sesión. El service worker guarda la
 * pantalla de sin conexión en la caché del navegador: si esa respuesta llevara
 * un Set-Cookie de sesión, cada vez que se sirve desde la caché repondría una
 * sesión vieja y dejaría al usuario en el login.
 *
 * Sacar la sesión obliga a sacar también la protección CSRF: es la que adjunta
 * la cookie XSRF-TOKEN a cada GET y para armarla lee el token de la sesión, que
 * acá no existe. Son tres rutas GET públicas sin efectos, así que no hay nada
 * que proteger.
 */
Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
])->group(function () {
    Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');

    // Tiene que vivir en la raíz: el scope máximo de un service worker es el
    // directorio desde el que se sirve, y necesita controlar todo el sitio.
    Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.service-worker');

    Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');

    // Bajo /icons/ a propósito: el service worker ya cachea ese prefijo con
    // CACHEABLE_PREFIXES, sin tocar nada.
    Route::get('/icons/splash/{width}x{height}.png', [PwaController::class, 'splash'])
        ->name('pwa.splash')
        ->where(['width' => '[0-9]+', 'height' => '[0-9]+']);
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
