<?php

use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\ListPurchases;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
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

test('the "ocultar anuladas" filter hides voided purchases', function () {
    $confirmada = Purchase::factory()->create(['status' => 'confirmada']);
    $anulada = Purchase::factory()->create(['status' => 'anulada']);

    Livewire::test(ListPurchases::class)
        ->assertCanSeeTableRecords([$confirmada, $anulada])
        ->filterTable('ocultar_anuladas', true)
        ->assertCanSeeTableRecords([$confirmada])
        ->assertCanNotSeeTableRecords([$anulada]);
});
