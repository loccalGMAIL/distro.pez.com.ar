<?php

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\StockMovement;
use App\Models\Warehouse;

test('numero is assigned automatically and increments sequentially', function () {
    $first = Sale::factory()->create(['numero' => null]);
    $second = Sale::factory()->create(['numero' => null]);

    expect($first->numero)->toBe('00000001');
    expect($second->numero)->toBe('00000002');
});

test('an explicit numero is respected and not overwritten', function () {
    $sale = Sale::factory()->create(['numero' => '99999999']);

    expect($sale->numero)->toBe('99999999');
});

test('deducirStock creates one negative venta movement per line and is idempotent', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $sale = Sale::factory()->create(['warehouse_id' => $warehouse->id, 'status' => 'confirmada']);
    SaleLine::factory()->for($sale)->create([
        'product_id' => $product->id,
        'cantidad' => 3,
        'costo_unit' => 150,
    ]);

    $sale->deducirStock();
    $sale->deducirStock(); // segunda llamada no debe duplicar movimientos

    $movements = StockMovement::where('source_type', Sale::class)->where('source_id', $sale->id)->get();

    expect($movements)->toHaveCount(1);
    expect((float) $movements->first()->quantity)->toBe(-3.0);
    expect((float) $movements->first()->unit_cost)->toBe(150.0);
    expect($movements->first()->type)->toBe('venta');
    expect($movements->first()->warehouse_id)->toBe($warehouse->id);
});

test('confirmar sets status to confirmada and deducts stock', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);
    SaleLine::factory()->for($sale)->create(['cantidad' => 2, 'costo_unit' => 80]);

    $sale->confirmar();

    expect($sale->fresh()->status)->toBe('confirmada');
    expect(StockMovement::where('source_type', Sale::class)->where('source_id', $sale->id)->where('type', 'venta')->count())->toBe(1);
});

test('anular on a confirmed sale reverses stock and sets status to anulada', function () {
    $sale = Sale::factory()->create(['status' => 'confirmada']);
    SaleLine::factory()->for($sale)->create(['cantidad' => 5, 'costo_unit' => 200]);
    $sale->deducirStock();

    $sale->anular();

    expect($sale->fresh()->status)->toBe('anulada');

    $reversal = StockMovement::where('source_type', Sale::class)
        ->where('source_id', $sale->id)
        ->where('type', 'devolucion')
        ->first();

    expect($reversal)->not->toBeNull();
    expect((float) $reversal->quantity)->toBe(5.0);
});

test('anular on a draft sale with no stock movements just voids it', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);

    $sale->anular();

    expect($sale->fresh()->status)->toBe('anulada');
    expect(StockMovement::where('source_type', Sale::class)->where('source_id', $sale->id)->exists())->toBeFalse();
});

test('anular reverses payment allocations tied to the sale', function () {
    $sale = Sale::factory()->create(['status' => 'confirmada']);
    $payment = Payment::factory()->create(['sin_imputar' => 0]);
    $allocation = PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'allocatable_type' => Sale::class,
        'allocatable_id' => $sale->id,
        'monto' => 500,
    ]);

    $sale->anular();

    expect(PaymentAllocation::find($allocation->id))->toBeNull();
    expect((float) $payment->fresh()->sin_imputar)->toBe(500.0);
});

test('anular is idempotent', function () {
    $sale = Sale::factory()->create(['status' => 'confirmada']);
    SaleLine::factory()->for($sale)->create(['cantidad' => 1, 'costo_unit' => 10]);
    $sale->deducirStock();

    $sale->anular();
    $sale->anular();

    expect(StockMovement::where('source_type', Sale::class)->where('source_id', $sale->id)->where('type', 'devolucion')->count())->toBe(1);
});
