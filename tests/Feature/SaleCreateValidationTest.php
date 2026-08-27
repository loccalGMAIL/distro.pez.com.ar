<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\CreateSale;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
    Customer::factory()->create(['predeterminado' => true]);
    PriceList::factory()->create(['predeterminada' => true]);
    Warehouse::factory()->create(['predeterminado' => true]);
});

test('finalizar is blocked and notifies when the sale total is zero', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateSale::class)
        ->fillForm([
            'lines' => ['item1' => [
                'product_id' => $product->id,
                'cantidad' => 1,
                'precio_unit' => 100,
                'descuento' => 100,
                'subtotal' => 0,
                'costo_unit' => 0,
            ]],
            'subtotal' => 0,
            'descuento' => 0,
            'total' => 0,
        ])
        ->call('create')
        ->assertNotified();

    expect(Sale::count())->toBe(0);
});

test('finalizar creates the sale when the total is greater than zero', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateSale::class)
        ->fillForm([
            'lines' => ['item1' => [
                'product_id' => $product->id,
                'cantidad' => 1,
                'precio_unit' => 100,
                'descuento' => 0,
                'subtotal' => 100,
                'costo_unit' => 0,
            ]],
            'subtotal' => 100,
            'descuento' => 0,
            'total' => 100,
        ])
        ->call('create');

    expect(Sale::count())->toBe(1);
    expect((float) Sale::first()->total)->toBe(100.0);
});
