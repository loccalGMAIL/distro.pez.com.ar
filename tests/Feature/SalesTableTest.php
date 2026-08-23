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
