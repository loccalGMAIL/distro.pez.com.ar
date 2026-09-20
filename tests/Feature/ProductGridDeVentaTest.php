<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;

/**
 * Crea una venta confirmada con una línea del producto por la cantidad indicada.
 */
function ventaConfirmadaDe(Product $product, float $cantidad): void
{
    $sale = Sale::factory()->create(['status' => 'confirmada']);
    SaleLine::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'cantidad' => $cantidad,
    ]);
}

test('favoritosParaVenta ranks products by units sold in confirmed sales', function () {
    $masVendido = Product::factory()->create(['nombre' => 'Zeta']);
    $menosVendido = Product::factory()->create(['nombre' => 'Alfa']);

    ventaConfirmadaDe($masVendido, 10);
    ventaConfirmadaDe($menosVendido, 3);

    $favoritos = Product::favoritosParaVenta();

    expect($favoritos->pluck('id')->all())->toBe([$masVendido->id, $menosVendido->id]);
});

test('favoritosParaVenta ignores draft and voided sales', function () {
    $confirmado = Product::factory()->create();
    $ignorado = Product::factory()->create();

    ventaConfirmadaDe($confirmado, 5);

    foreach (['borrador', 'anulada'] as $status) {
        $sale = Sale::factory()->create(['status' => $status]);
        SaleLine::factory()->create(['sale_id' => $sale->id, 'product_id' => $ignorado->id, 'cantidad' => 999]);
    }

    $favoritos = Product::favoritosParaVenta();

    expect($favoritos->pluck('id')->all())->toBe([$confirmado->id]);
});

test('favoritosParaVenta only includes products that were actually sold', function () {
    $vendido = Product::factory()->create();
    Product::factory()->create(); // Nunca vendido: no debe aparecer como favorito.

    ventaConfirmadaDe($vendido, 2);

    expect(Product::favoritosParaVenta()->pluck('id')->all())->toBe([$vendido->id]);
});

test('favoritosParaVenta caps the result at the given limit (default four)', function () {
    $products = Product::factory()->count(5)->create();

    foreach ($products as $index => $product) {
        ventaConfirmadaDe($product, $index + 1);
    }

    expect(Product::favoritosParaVenta())->toHaveCount(4);
    expect(Product::favoritosParaVenta(2))->toHaveCount(2);
});

test('paraGridDeVenta returns favorites plus the rest alphabetically, excluding favorites', function () {
    $favorito = Product::factory()->create(['nombre' => 'Producto Vendido']);
    $charlie = Product::factory()->create(['nombre' => 'Charlie']);
    $bravo = Product::factory()->create(['nombre' => 'Bravo']);

    ventaConfirmadaDe($favorito, 7);

    ['favorites' => $favorites, 'others' => $others] = Product::paraGridDeVenta();

    expect($favorites->pluck('id')->all())->toBe([$favorito->id]);
    expect($others->pluck('nombre')->all())->toBe(['Bravo', 'Charlie']);
    expect($others->pluck('id'))->not->toContain($favorito->id);
});

test('paraGridDeVenta excludes inactive products from both groups', function () {
    $activoVendido = Product::factory()->create(['nombre' => 'Activo', 'activo' => true]);
    $inactivo = Product::factory()->create(['nombre' => 'Inactivo', 'activo' => false]);

    ventaConfirmadaDe($activoVendido, 4);
    ventaConfirmadaDe($inactivo, 99);

    ['favorites' => $favorites, 'others' => $others] = Product::paraGridDeVenta();

    expect($favorites->pluck('id')->all())->toBe([$activoVendido->id]);
    expect($others->pluck('id'))->not->toContain($inactivo->id);
    expect($favorites->pluck('id'))->not->toContain($inactivo->id);
});
