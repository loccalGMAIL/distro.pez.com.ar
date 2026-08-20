<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard/login')->name('home');

Route::get('/ventas/{sale}/comprobante', function (Sale $sale) {
    $comprobanteNumero = $sale->comprobanteNumero();

    abort_unless($comprobanteNumero, 404);

    return $sale->comprobantePdf()->stream("comprobante-{$comprobanteNumero}.pdf");
})->middleware('auth')->name('sales.comprobante');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
