<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las rutas fuera del panel de Filament (ej. sales.comprobante,
        // purchases.archivo) usan el middleware 'auth' genérico, que por
        // defecto intenta redirigir a la ruta con nombre "login" -que no
        // existe, Filament registra la suya con otro nombre por panel- y
        // rompe con un 500 para un invitado. Se apunta al login del panel.
        $middleware->redirectGuestsTo(fn () => route('filament.dashboard.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
