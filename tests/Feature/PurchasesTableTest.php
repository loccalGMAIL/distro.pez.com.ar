<?php

use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\ListPurchases;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
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

test('the "ocultar anuladas" filter is enabled by default, hiding voided purchases', function () {
    $confirmada = Purchase::factory()->create(['status' => 'confirmada']);
    $anulada = Purchase::factory()->create(['status' => 'anulada']);

    Livewire::test(ListPurchases::class)
        ->assertCanSeeTableRecords([$confirmada])
        ->assertCanNotSeeTableRecords([$anulada]);
});

test('turning the "ocultar anuladas" filter off shows voided purchases again', function () {
    $confirmada = Purchase::factory()->create(['status' => 'confirmada']);
    $anulada = Purchase::factory()->create(['status' => 'anulada']);

    Livewire::test(ListPurchases::class)
        ->filterTable('ocultar_anuladas', false)
        ->assertCanSeeTableRecords([$confirmada, $anulada]);
});

test('the purchases table can be filtered by product', function () {
    $product = Product::factory()->create();
    $withProduct = Purchase::factory()->create();
    $withoutProduct = Purchase::factory()->create();
    PurchaseLine::factory()->create(['purchase_id' => $withProduct->id, 'product_id' => $product->id]);
    PurchaseLine::factory()->create(['purchase_id' => $withoutProduct->id]);

    Livewire::test(ListPurchases::class)
        ->filterTable('product_id', $product->id)
        ->assertCanSeeTableRecords([$withProduct])
        ->assertCanNotSeeTableRecords([$withoutProduct]);
});

test('filtering by product shows the units of that product in each purchase', function () {
    $product = Product::factory()->create();
    $purchase = Purchase::factory()->create();
    PurchaseLine::factory()->create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'cantidad' => 4]);
    PurchaseLine::factory()->create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'cantidad' => 3]);
    PurchaseLine::factory()->create(['purchase_id' => $purchase->id, 'cantidad' => 50]);

    Livewire::test(ListPurchases::class)
        ->assertTableColumnHidden('product_units')
        ->filterTable('product_id', $product->id)
        ->assertTableColumnVisible('product_units')
        ->assertTableColumnStateSet('product_units', 7, $purchase);
});
