<?php

use App\Models\Customer;
use App\Models\Sale;

test('marking a customer as default unmarks the previous default', function () {
    $a = Customer::factory()->create(['predeterminado' => true]);
    $b = Customer::factory()->create(['predeterminado' => true]);

    expect($a->fresh()->predeterminado)->toBeFalse();
    expect($b->fresh()->predeterminado)->toBeTrue();
});

test('a new customer gets the next correlative codigo automatically', function () {
    Customer::factory()->create(['codigo' => 'CLI-000041']);
    Customer::factory()->create(['codigo' => 'CLI-000007'])->delete();

    $customer = Customer::factory()->create(['codigo' => null]);

    expect($customer->codigo)->toBe('CLI-000042');
});

test('a codigo given explicitly is kept', function () {
    $customer = Customer::factory()->create(['codigo' => 'MANUAL-1']);

    expect($customer->codigo)->toBe('MANUAL-1');
});

test('a sale keeps showing its customer after the customer is soft deleted', function () {
    $sale = Sale::factory()->create();
    $sale->customer->delete();

    expect($sale->fresh()->customer)->not->toBeNull();
});
