<?php

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;

test('creating an allocation against a confirmed purchase reduces its saldo and the supplier balance', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 1000]);
    $purchase->confirmar();

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 300,
        'sin_imputar' => 0,
    ]);
    $allocation = PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'allocatable_type' => Purchase::class,
        'allocatable_id' => $purchase->id,
        'monto' => 300,
    ]);

    expect((float) $purchase->fresh()->saldo)->toBe(700.0);
    expect((float) $supplier->fresh()->balance)->toBe(700.0);

    // El pago también sube a 500 junto con la allocation, para que
    // sin_imputar se mantenga en 0 y este paso siga aislando el efecto de
    // la allocation (si el pago se quedara en 300, la allocation de 500
    // dejaría el pago "sobre-imputado" y el balance del proveedor bajaría
    // de más, que es harina de otro test).
    $payment->update(['monto' => 500]);
    $allocation->update(['monto' => 500]);

    expect((float) $purchase->fresh()->saldo)->toBe(500.0);
    expect((float) $supplier->fresh()->balance)->toBe(500.0);

    $allocation->delete();

    // El saldo de la compra vuelve al total (1000), pero el balance del
    // proveedor queda neto de los $500 que ya cobró y todavía no están
    // aplicados a ningún comprobante (Payment::sin_imputar): 1000 − 500.
    expect((float) $purchase->fresh()->saldo)->toBe(1000.0);
    expect((float) $payment->fresh()->sin_imputar)->toBe(500.0);
    expect((float) $supplier->fresh()->balance)->toBe(500.0);
});

test('an allocation against a sale does not touch any purchase saldo or supplier balance', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $sale = Sale::factory()->create(['status' => 'confirmada']);
    $payment = Payment::factory()->create(['sin_imputar' => 0]);

    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'allocatable_type' => Sale::class,
        'allocatable_id' => $sale->id,
        'monto' => 200,
    ]);

    expect((float) $supplier->fresh()->balance)->toBe(0.0);
});
