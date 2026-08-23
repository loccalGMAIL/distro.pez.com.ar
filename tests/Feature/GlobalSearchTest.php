<?php

use App\Filament\Clusters\Catalog\Resources\PriceLists\PriceListResource;
use App\Filament\Clusters\Catalog\Resources\Products\ProductResource;
use App\Filament\Clusters\Catalog\Resources\Warehouses\WarehouseResource;
use App\Filament\Clusters\Finance\Resources\Expenses\ExpenseResource;
use App\Filament\Clusters\Finance\Resources\Payments\PaymentResource;
use App\Filament\Clusters\Partners\Resources\Customers\CustomerResource;
use App\Filament\Clusters\Partners\Resources\Suppliers\SupplierResource;
use App\Filament\Clusters\Purchases\Resources\Purchases\PurchaseResource;
use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Filament\Clusters\Settings\Resources\Users\UserResource;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('product global search matches by nombre, sku and barcode', function () {
    $product = Product::factory()->create(['nombre' => 'Tornillo Phillips 3/4', 'sku' => 'SKU-9911', 'barcode' => '7791234567890']);

    expect(ProductResource::getGlobalSearchResults('Phillips')->pluck('title'))->toContain('Tornillo Phillips 3/4');
    expect(ProductResource::getGlobalSearchResults('SKU-9911')->pluck('title'))->toContain('Tornillo Phillips 3/4');
    expect(ProductResource::getGlobalSearchResults('7791234567890')->pluck('title'))->toContain('Tornillo Phillips 3/4');
});

test('price list global search matches by nombre', function () {
    PriceList::factory()->create(['nombre' => 'Mayorista Especial']);

    expect(PriceListResource::getGlobalSearchResults('Mayorista Especial')->pluck('title'))->toContain('Mayorista Especial');
});

test('warehouse global search matches by nombre', function () {
    Warehouse::factory()->create(['nombre' => 'Depósito Central']);

    expect(WarehouseResource::getGlobalSearchResults('Central')->pluck('title'))->toContain('Depósito Central');
});

test('customer global search matches by razon_social and cuit', function () {
    $customer = Customer::factory()->create(['razon_social' => 'Ferretería El Tornillo SA', 'cuit' => '20-11223344-5']);

    expect(CustomerResource::getGlobalSearchResults('El Tornillo')->pluck('title'))->toContain('Ferretería El Tornillo SA');
    expect(CustomerResource::getGlobalSearchResults('20-11223344-5')->pluck('title'))->toContain('Ferretería El Tornillo SA');
});

test('supplier global search matches by razon_social', function () {
    Supplier::factory()->create(['razon_social' => 'Distribuidora Norte SRL']);

    expect(SupplierResource::getGlobalSearchResults('Norte')->pluck('title'))->toContain('Distribuidora Norte SRL');
});

test('sale global search matches by numero and by customer razon_social', function () {
    $customer = Customer::factory()->create(['razon_social' => 'Cliente Buscable SA']);
    $sale = Sale::factory()->create(['numero' => '00001-00000123', 'customer_id' => $customer->id]);

    expect(SaleResource::getGlobalSearchResults('00001-00000123')->pluck('title'))->toContain('00001-00000123');
    expect(SaleResource::getGlobalSearchResults('Cliente Buscable')->pluck('title'))->toContain('00001-00000123');
});

test('purchase global search matches by numero and by supplier razon_social', function () {
    $supplier = Supplier::factory()->create(['razon_social' => 'Proveedor Buscable SRL']);
    $purchase = Purchase::factory()->create(['numero' => '0001-00009999', 'supplier_id' => $supplier->id]);

    expect(PurchaseResource::getGlobalSearchResults('0001-00009999')->pluck('title'))->toContain('0001-00009999');
    expect(PurchaseResource::getGlobalSearchResults('Proveedor Buscable')->pluck('title'))->toContain('0001-00009999');
});

test('expense global search matches by descripcion and comprobante_numero', function () {
    Expense::factory()->create(['descripcion' => 'Alquiler del depósito de agosto', 'comprobante_numero' => 'FC-A-0002-00012345']);

    expect(ExpenseResource::getGlobalSearchResults('Alquiler del depósito')->pluck('title'))->toContain('Alquiler del depósito de agosto');
    expect(ExpenseResource::getGlobalSearchResults('FC-A-0002-00012345')->pluck('title'))->toContain('Alquiler del depósito de agosto');
});

test('user global search matches by name and email', function () {
    User::factory()->create(['name' => 'Ana Buscable', 'email' => 'ana.buscable@example.com']);

    expect(UserResource::getGlobalSearchResults('Ana Buscable')->pluck('title'))->toContain('Ana Buscable');
    expect(UserResource::getGlobalSearchResults('ana.buscable@example.com')->pluck('title'))->toContain('Ana Buscable');
});

test('payment global search matches by party razon_social and composes a title from party and monto', function () {
    $supplier = Supplier::factory()->create(['razon_social' => 'Proveedor Con Pago SRL']);
    Payment::factory()->create(['party_type' => Supplier::class, 'party_id' => $supplier->id, 'monto' => 1500.5]);

    $results = PaymentResource::getGlobalSearchResults('Proveedor Con Pago');

    expect($results->pluck('title'))->toContain('Proveedor Con Pago SRL — $1.500,50');
});
