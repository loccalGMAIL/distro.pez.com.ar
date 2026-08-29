<?php

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\StockMovement;
use App\Models\Warehouse;

test('aumentarStock creates one positive compra movement per line, updates costo_ultimo and is idempotent', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['costo_ultimo' => 10]);
    $purchase = Purchase::factory()->create(['warehouse_id' => $warehouse->id, 'status' => 'borrador']);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 4,
        'costo_unit' => 250,
    ]);

    $purchase->aumentarStock();
    $purchase->aumentarStock(); // segunda llamada no debe duplicar movimientos

    $movements = StockMovement::where('source_type', Purchase::class)->where('source_id', $purchase->id)->get();

    expect($movements)->toHaveCount(1);
    expect((float) $movements->first()->quantity)->toBe(4.0);
    expect((float) $movements->first()->unit_cost)->toBe(250.0);
    expect($movements->first()->type)->toBe('compra');
    expect($movements->first()->warehouse_id)->toBe($warehouse->id);
    expect((float) $product->fresh()->costo_ultimo)->toBe(250.0);
});

test('confirmar sets status to confirmada and increases stock', function () {
    $purchase = Purchase::factory()->create(['status' => 'borrador']);
    PurchaseLine::factory()->for($purchase)->create(['cantidad' => 2, 'costo_unit' => 80]);

    $purchase->confirmar();

    expect($purchase->fresh()->status)->toBe('confirmada');
    expect(StockMovement::where('source_type', Purchase::class)->where('source_id', $purchase->id)->where('type', 'compra')->count())->toBe(1);
});

test('anular on a confirmed purchase reverses stock and sets status to anulada without touching costo_ultimo', function () {
    $product = Product::factory()->create(['costo_ultimo' => 10]);
    $purchase = Purchase::factory()->create(['status' => 'confirmada']);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 5,
        'costo_unit' => 200,
    ]);
    $purchase->aumentarStock();

    $purchase->anular();

    expect($purchase->fresh()->status)->toBe('anulada');

    $reversal = StockMovement::where('source_type', Purchase::class)
        ->where('source_id', $purchase->id)
        ->where('type', 'devolucion_prov')
        ->first();

    expect($reversal)->not->toBeNull();
    expect((float) $reversal->quantity)->toBe(-5.0);
    expect((float) $product->fresh()->costo_ultimo)->toBe(200.0);
});

test('anular clears numero so the same comprobante can be reloaded, but keeps archivo_path', function () {
    $purchase = Purchase::factory()->create([
        'status' => 'confirmada',
        'numero' => '0001-00001234',
        'archivo_path' => 'purchases/factura.pdf',
    ]);

    $purchase->anular();

    $fresh = $purchase->fresh();
    expect($fresh->numero)->toBeNull();
    expect($fresh->archivo_path)->toBe('purchases/factura.pdf');
});

test('anular allows recreating a purchase with the same supplier, tipo_comprobante and numero', function () {
    $purchase = Purchase::factory()->create([
        'status' => 'confirmada',
        'numero' => '0001-00001234',
    ]);

    $purchase->anular();

    $nueva = Purchase::factory()->create([
        'supplier_id' => $purchase->supplier_id,
        'tipo_comprobante' => $purchase->tipo_comprobante,
        'numero' => '0001-00001234',
    ]);

    expect($nueva->exists)->toBeTrue();
});

test('anular on a draft purchase with no stock movements just voids it', function () {
    $purchase = Purchase::factory()->create(['status' => 'borrador']);

    $purchase->anular();

    expect($purchase->fresh()->status)->toBe('anulada');
    expect(StockMovement::where('source_type', Purchase::class)->where('source_id', $purchase->id)->exists())->toBeFalse();
});

test('anular reverses payment allocations tied to the purchase', function () {
    $purchase = Purchase::factory()->create(['status' => 'confirmada']);
    $payment = Payment::factory()->create(['sin_imputar' => 0]);
    $allocation = PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'allocatable_type' => Purchase::class,
        'allocatable_id' => $purchase->id,
        'monto' => 500,
    ]);

    $purchase->anular();

    expect(PaymentAllocation::find($allocation->id))->toBeNull();
    expect((float) $payment->fresh()->sin_imputar)->toBe(500.0);
});

test('anular is idempotent', function () {
    $purchase = Purchase::factory()->create(['status' => 'confirmada']);
    PurchaseLine::factory()->for($purchase)->create(['cantidad' => 1, 'costo_unit' => 10]);
    $purchase->aumentarStock();

    $purchase->anular();
    $purchase->anular();

    expect(StockMovement::where('source_type', Purchase::class)->where('source_id', $purchase->id)->where('type', 'devolucion_prov')->count())->toBe(1);
});
