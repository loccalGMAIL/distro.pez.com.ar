<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/dashboard/login')->name('home');

Route::get('/ventas/{sale}/comprobante', function (Sale $sale) {
    $comprobanteNumero = $sale->comprobanteNumero();

    abort_unless(filled($comprobanteNumero), 404);

    return $sale->comprobantePdf()->stream("comprobante-{$comprobanteNumero}.pdf");
})->middleware('auth')->name('sales.comprobante');

Route::get('/compras/{purchase}/archivo', function (Purchase $purchase) {
    abort_unless(filled($purchase->archivo_path), 404);
    abort_unless(Storage::disk('local')->exists($purchase->archivo_path), 404);

    return Storage::disk('local')->response($purchase->archivo_path);
})->middleware('auth')->name('purchases.archivo');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
