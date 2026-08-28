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
    'settings/users',
    'settings/activity-logs',
    'settings/time-entries',
];

// Ventas y Compras no tienen página /create propia (se crean por modal desde
// el listado); ActivityLogs es de solo lectura (sin create page). Se
// excluyen del chequeo genérico de "create page loads".
$createableResources = array_values(array_diff($resources, ['sales/sales', 'purchases/purchases', 'settings/activity-logs']));

test('each filament resource index page loads for an admin', function (string $resource) {
    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin)
        ->get("/dashboard/{$resource}")
        ->assertSuccessful();
})->with($resources);

test('each filament resource create page loads for an admin', function (string $resource) {
    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin)
        ->get("/dashboard/{$resource}/create")
        ->assertSuccessful();
})->with($createableResources);

test('purchase edit page renders for a draft purchase', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $purchase = Purchase::factory()->has(PurchaseLine::factory()->count(2), 'lines')->create(['status' => 'borrador']);

    $this->actingAs($admin)
        ->get("/dashboard/purchases/purchases/{$purchase->id}/edit")
        ->assertSuccessful();
});

test('purchase edit page redirects away for a confirmed purchase', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $purchase = Purchase::factory()->has(PurchaseLine::factory()->count(2), 'lines')->create(['status' => 'confirmada']);

    $this->actingAs($admin)
        ->get("/dashboard/purchases/purchases/{$purchase->id}/edit")
        ->assertRedirect('/dashboard/purchases/purchases');
});

test('payment edit page renders the allocations relation manager', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $payment = Payment::factory()->has(PaymentAllocation::factory()->count(2), 'allocations')->create();

    $this->actingAs($admin)
        ->get("/dashboard/finance/payments/{$payment->id}/edit")
        ->assertSuccessful();
});
