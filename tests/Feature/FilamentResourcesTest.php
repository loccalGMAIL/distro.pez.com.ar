<?php

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\User;

$resources = [
    'catalog/warehouses',
    'catalog/products',
    'catalog/price-lists',
    'partners/customers',
    'partners/suppliers',
    'finance/expenses',
    'purchases/purchases',
    'sales/sales',
    'finance/payments',
    'purchases/stock-movements',
];

// Ventas no tiene página /create propia (se crea por modal desde el listado),
// así que se excluye del chequeo genérico de "create page loads".
$createableResources = array_values(array_diff($resources, ['sales/sales']));

test('each filament resource index page loads for an admin', function (string $resource) {
    $admin = User::factory()->create(['role' => 'admin', 'activo' => true]);

    $this->actingAs($admin)
        ->get("/dashboard/{$resource}")
        ->assertSuccessful();
})->with($resources);

test('each filament resource create page loads for an admin', function (string $resource) {
    $admin = User::factory()->create(['role' => 'admin', 'activo' => true]);

    $this->actingAs($admin)
        ->get("/dashboard/{$resource}/create")
        ->assertSuccessful();
})->with($createableResources);

test('purchase edit page renders the lines relation manager', function () {
    $admin = User::factory()->create(['role' => 'admin', 'activo' => true]);
    $purchase = Purchase::factory()->has(PurchaseLine::factory()->count(2), 'lines')->create();

    $this->actingAs($admin)
        ->get("/dashboard/purchases/purchases/{$purchase->id}/edit")
        ->assertSuccessful();
});

test('payment edit page renders the allocations relation manager', function () {
    $admin = User::factory()->create(['role' => 'admin', 'activo' => true]);
    $payment = Payment::factory()->has(PaymentAllocation::factory()->count(2), 'allocations')->create();

    $this->actingAs($admin)
        ->get("/dashboard/finance/payments/{$payment->id}/edit")
        ->assertSuccessful();
});
