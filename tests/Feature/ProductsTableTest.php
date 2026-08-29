<?php

use App\Filament\Clusters\Catalog\Resources\Products\Pages\ListProducts;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('product prices are shown with a $ denomination, not ARS', function () {
    Product::factory()->create(['costo_ultimo' => 1000]);

    Livewire::test(ListProducts::class)
        ->assertSeeHtml('$')
        ->assertDontSeeHtml('ARS');
});

test('price list columns are ordered minorista, mayorista, then vip', function () {
    PriceList::factory()->create(['nombre' => 'Lista VIP']);
    PriceList::factory()->create(['nombre' => 'Mayorista']);
    PriceList::factory()->create(['nombre' => 'Minorista']);

    $html = Livewire::test(ListProducts::class)->html();

    $minoristaPosition = mb_strpos($html, 'Minorista');
    $mayoristaPosition = mb_strpos($html, 'Mayorista');
    $vipPosition = mb_strpos($html, 'Lista VIP');

    expect($minoristaPosition)->not->toBeFalse()
        ->and($mayoristaPosition)->not->toBeFalse()
        ->and($vipPosition)->not->toBeFalse()
        ->and($minoristaPosition)->toBeLessThan($mayoristaPosition)
        ->and($mayoristaPosition)->toBeLessThan($vipPosition);
});

test('the stock column shows the total quantity summed across warehouses', function () {
    $product = Product::factory()->create();
    $deposito = Warehouse::factory()->create(['nombre' => 'Depósito Central']);
    $camion = Warehouse::factory()->create(['nombre' => 'Camión Reparto']);
    StockMovement::factory()->create(['product_id' => $product->id, 'warehouse_id' => $deposito->id, 'quantity' => 10]);
    StockMovement::factory()->create(['product_id' => $product->id, 'warehouse_id' => $camion->id, 'quantity' => 5]);

    Livewire::test(ListProducts::class)->assertSeeHtml('15');
});

test('the stock column tooltip breaks down the total by warehouse', function () {
    $product = Product::factory()->create();
    $deposito = Warehouse::factory()->create(['nombre' => 'Depósito Central']);
    StockMovement::factory()->create(['product_id' => $product->id, 'warehouse_id' => $deposito->id, 'quantity' => 10]);

    $html = Livewire::test(ListProducts::class)->html();

    expect($html)->toContain('Depósito Central: 10');
});

test('the stock column is highlighted when the total is at or below min_stock', function () {
    $lowStock = Product::factory()->create(['min_stock' => 10]);
    $warehouse = Warehouse::factory()->create();
    StockMovement::factory()->create(['product_id' => $lowStock->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);

    $okStock = Product::factory()->create(['min_stock' => 1]);
    StockMovement::factory()->create(['product_id' => $okStock->id, 'warehouse_id' => $warehouse->id, 'quantity' => 20]);

    Livewire::test(ListProducts::class)
        ->assertTableColumnStateSet('stock_total', 5.0, $lowStock)
        ->assertTableColumnStateSet('stock_total', 20.0, $okStock);
});

test('the ajustarStock action creates a stock movement of type ajuste for the product', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);
    $warehouse = Warehouse::factory()->create();

    Livewire::test(ListProducts::class)
        ->callTableAction('ajustarStock', $product, data: [
            'warehouse_id' => $warehouse->id,
            'quantity' => 7,
            'motivo' => 'Conteo físico',
        ])
        ->assertNotified('Stock ajustado');

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 7,
        'type' => 'ajuste',
        'motivo' => 'Conteo físico',
    ]);
});
