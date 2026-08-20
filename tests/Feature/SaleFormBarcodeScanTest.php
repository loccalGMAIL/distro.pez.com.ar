<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\ListSales;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'activo' => true]));
    Customer::factory()->create(['predeterminado' => true]);
    PriceList::factory()->create(['predeterminada' => true]);
    Warehouse::factory()->create(['predeterminado' => true]);
});

test('scanning a known barcode fills product, price and cost on the line', function () {
    $product = Product::factory()->create();

    Livewire::test(ListSales::class)
        ->mountAction(CreateAction::class)
        ->fillForm(['lines' => ['item1' => ['product_id' => null, 'cantidad' => 1, 'descuento' => 0]]])
        ->callAction(
            TestAction::make('scanBarcode')
                ->schemaComponent('lines.item1.product_id')
                ->arguments(['barcode' => $product->barcode]),
        )
        ->assertSchemaStateSet(function (array $state) use ($product) {
            expect((int) $state['lines']['item1']['product_id'])->toBe($product->id);
            expect((float) $state['lines']['item1']['costo_unit'])->toBe((float) $product->costo_ultimo);

            return [];
        });
});

test('scanning an unknown barcode leaves the line empty and notifies', function () {
    Livewire::test(ListSales::class)
        ->mountAction(CreateAction::class)
        ->fillForm(['lines' => ['item1' => ['product_id' => null, 'cantidad' => 1, 'descuento' => 0]]])
        ->callAction(
            TestAction::make('scanBarcode')
                ->schemaComponent('lines.item1.product_id')
                ->arguments(['barcode' => 'no-existe']),
        )
        ->assertNotified()
        ->assertSchemaStateSet(function (array $state) {
            expect($state['lines']['item1']['product_id'])->toBeNull();

            return [];
        });
});
