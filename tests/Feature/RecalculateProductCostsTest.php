<?php

use App\Models\PerceptionType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;

test('recalculates the costo final of purchase lines and leaves each product with its most recent confirmed purchase', function () {
    $product = Product::factory()->create(['costo_ultimo' => 0, 'costo_neto_ultimo' => 0]);
    $tipoQueAfecta = PerceptionType::factory()->create(['afecta_costo' => true]);

    $compraVieja = Purchase::factory()->create(['status' => 'confirmada', 'fecha' => '2026-01-01', 'descuento' => 0]);
    PurchaseLine::factory()->for($compraVieja)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 100,
        'costo_final' => 0,
        'subtotal' => 100,
    ]);

    $compraReciente = Purchase::factory()->create(['status' => 'confirmada', 'fecha' => '2026-02-01', 'descuento' => 0]);
    PurchaseLine::factory()->for($compraReciente)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 200,
        'costo_final' => 0,
        'subtotal' => 200,
    ]);
    $compraReciente->perceptions()->create(['perception_type_id' => $tipoQueAfecta->id, 'monto' => 20]);

    $this->artisan('app:recalculate-product-costs')->assertSuccessful();

    expect((float) $product->fresh()->costo_ultimo)->toBe(220.0);
    expect((float) $product->fresh()->costo_neto_ultimo)->toBe(200.0);
    expect((float) $compraReciente->lines()->first()->costo_final)->toBe(220.0);
    expect((float) $compraVieja->lines()->first()->costo_final)->toBe(100.0);
});

test('--dry-run does not write anything', function () {
    $product = Product::factory()->create(['costo_ultimo' => 0, 'costo_neto_ultimo' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'confirmada', 'descuento' => 0]);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 100,
        'costo_final' => 0,
        'subtotal' => 100,
    ]);

    $this->artisan('app:recalculate-product-costs', ['--dry-run' => true])->assertSuccessful();

    expect((float) $product->fresh()->costo_ultimo)->toBe(0.0);
    expect((float) $product->fresh()->costo_neto_ultimo)->toBe(0.0);
    expect((float) $purchase->lines()->first()->costo_final)->toBe(0.0);
});

test('purchases in borrador or anulada are ignored', function () {
    $product = Product::factory()->create(['costo_ultimo' => 0, 'costo_neto_ultimo' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'descuento' => 0]);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 100,
        'costo_final' => 0,
        'subtotal' => 100,
    ]);

    $this->artisan('app:recalculate-product-costs')->assertSuccessful();

    expect((float) $product->fresh()->costo_ultimo)->toBe(0.0);
});
