<?php

use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\ListPurchases;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['activo' => true]));
});

test('the purchases table can be filtered by supplier', function () {
    $supplierA = Supplier::factory()->create();
    $supplierB = Supplier::factory()->create();

    $purchaseA = Purchase::factory()->create(['supplier_id' => $supplierA->id]);
    $purchaseB = Purchase::factory()->create(['supplier_id' => $supplierB->id]);

    Livewire::test(ListPurchases::class)
        ->assertCanSeeTableRecords([$purchaseA, $purchaseB])
        ->filterTable('supplier_id', $supplierA->id)
        ->assertCanSeeTableRecords([$purchaseA])
        ->assertCanNotSeeTableRecords([$purchaseB]);
});
