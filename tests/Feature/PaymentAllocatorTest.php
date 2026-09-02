<?php

use App\Filament\Clusters\Finance\Resources\Payments\Pages\CreatePayment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PaymentAllocator;
use Livewire\Livewire;

test('a payment that exactly covers two purchases zeroes out both and the payment itself', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $older = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 600, 'fecha' => '2026-08-01']);
    $older->confirmar();
    $newer = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 400, 'fecha' => '2026-08-15']);
    $newer->confirmar();

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 1000,
        'sin_imputar' => 1000,
    ]);

    app(PaymentAllocator::class)->allocate($payment);

    expect((float) $older->fresh()->saldo)->toBe(0.0);
    expect((float) $newer->fresh()->saldo)->toBe(0.0);
    expect((float) $payment->fresh()->sin_imputar)->toBe(0.0);
    expect((float) $supplier->fresh()->balance)->toBe(0.0);
});

test('a partial payment is applied FIFO by fecha, oldest purchase first', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $older = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 600, 'fecha' => '2026-08-01']);
    $older->confirmar();
    $newer = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 400, 'fecha' => '2026-08-15']);
    $newer->confirmar();

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 700,
        'sin_imputar' => 700,
    ]);

    app(PaymentAllocator::class)->allocate($payment);

    // La compra más vieja queda saldada del todo, la más nueva recibe solo
    // el remanente ($100 de los $700, tras los $600 de la primera).
    expect((float) $older->fresh()->saldo)->toBe(0.0);
    expect((float) $newer->fresh()->saldo)->toBe(300.0);
    expect((float) $payment->fresh()->sin_imputar)->toBe(0.0);
    expect((float) $supplier->fresh()->balance)->toBe(300.0);
});

test('a payment larger than the debt leaves the excess as sin_imputar and a negative (favorable) balance', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 300]);
    $purchase->confirmar();

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 500,
        'sin_imputar' => 500,
    ]);

    app(PaymentAllocator::class)->allocate($payment);

    expect((float) $purchase->fresh()->saldo)->toBe(0.0);
    expect((float) $payment->fresh()->sin_imputar)->toBe(200.0);
    expect((float) $supplier->fresh()->balance)->toBe(-200.0);
});

test('a payment with no confirmed purchases pending stays fully unallocated as a credit', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 1000]);

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 90000,
        'sin_imputar' => 90000,
    ]);

    app(PaymentAllocator::class)->allocate($payment);

    expect(PaymentAllocation::where('payment_id', $payment->id)->count())->toBe(0);
    expect((float) $payment->fresh()->sin_imputar)->toBe(90000.0);
    expect((float) $supplier->fresh()->balance)->toBe(-90000.0);
});

test('allocate is idempotent: running it again on an already-allocated payment does not duplicate allocations', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 300]);
    $purchase->confirmar();

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 300,
        'sin_imputar' => 300,
    ]);

    $allocator = app(PaymentAllocator::class);
    $allocator->allocate($payment);
    $allocator->allocate($payment->fresh());

    expect(PaymentAllocation::where('payment_id', $payment->id)->count())->toBe(1);
    expect((float) $purchase->fresh()->saldo)->toBe(0.0);
});

test('soft-deleting a payment reverses its allocations and restores saldo and balance', function () {
    $supplier = Supplier::factory()->create(['balance' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 300]);
    $purchase->confirmar();

    $payment = Payment::factory()->create([
        'party_type' => Supplier::class,
        'party_id' => $supplier->id,
        'monto' => 300,
        'sin_imputar' => 300,
    ]);
    app(PaymentAllocator::class)->allocate($payment);

    expect((float) $purchase->fresh()->saldo)->toBe(0.0);

    $payment->delete();

    expect(PaymentAllocation::where('payment_id', $payment->id)->count())->toBe(0);
    expect((float) $purchase->fresh()->saldo)->toBe(300.0);
    expect((float) $supplier->fresh()->balance)->toBe(300.0);
});

test('creating a payment through the Filament page imputes it automatically', function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));

    $supplier = Supplier::factory()->create(['balance' => 0]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'supplier_id' => $supplier->id, 'total' => 300]);
    $purchase->confirmar();

    Livewire::test(CreatePayment::class)
        ->fillForm([
            'party_type' => Supplier::class,
            'party_id' => $supplier->id,
            'direccion' => 'egreso',
            'fecha' => '2026-09-01',
            'monto' => 300,
            'medio_pago' => 'efectivo',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect((float) $purchase->fresh()->saldo)->toBe(0.0);
    expect((float) $supplier->fresh()->balance)->toBe(0.0);
});
