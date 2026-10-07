<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\ListSales;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('the sales table can be filtered by customer', function () {
    $customerA = Customer::factory()->create();
    $customerB = Customer::factory()->create();

    $saleA = Sale::factory()->create(['customer_id' => $customerA->id]);
    $saleB = Sale::factory()->create(['customer_id' => $customerB->id]);

    Livewire::test(ListSales::class)
        ->assertCanSeeTableRecords([$saleA, $saleB])
        ->filterTable('customer_id', $customerA->id)
        ->assertCanSeeTableRecords([$saleA])
        ->assertCanNotSeeTableRecords([$saleB]);
});

test('the sales table lists the most recent dates first', function () {
    $older = Sale::factory()->create(['fecha' => now()->subDays(10)]);
    $newest = Sale::factory()->create(['fecha' => now()]);
    $middle = Sale::factory()->create(['fecha' => now()->subDays(3)]);

    Livewire::test(ListSales::class)
        ->assertCanSeeTableRecords([$newest, $middle, $older], inOrder: true);
});
