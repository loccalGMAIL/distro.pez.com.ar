<?php

use App\Filament\Widgets\ProductoMasVendidoWidget;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('it shows the product with the most quantity sold this month among confirmed sales', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    $topProduct = Product::factory()->create(['nombre' => 'Producto Top']);
    $otherProduct = Product::factory()->create(['nombre' => 'Producto Menor']);
    $ignoredProduct = Product::factory()->create(['nombre' => 'Producto Ignorado']);

    $confirmedSale = Sale::factory()->create(['status' => 'confirmada', 'fecha' => now()]);
    SaleLine::factory()->create(['sale_id' => $confirmedSale->id, 'product_id' => $topProduct->id, 'cantidad' => 10]);
    SaleLine::factory()->create(['sale_id' => $confirmedSale->id, 'product_id' => $otherProduct->id, 'cantidad' => 3]);

    // Decoy: venta en borrador, no debe contar aunque tenga más cantidad.
    $draftSale = Sale::factory()->create(['status' => 'borrador', 'fecha' => now()]);
    SaleLine::factory()->create(['sale_id' => $draftSale->id, 'product_id' => $ignoredProduct->id, 'cantidad' => 999]);

    Livewire::test(ProductoMasVendidoWidget::class)
        ->assertSee('Producto Top')
        ->assertDontSee('Producto Ignorado');
});

test('it shows a fallback message when there are no sales this month', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Livewire::test(ProductoMasVendidoWidget::class)
        ->assertSee('Sin ventas este mes');
});

test('it counts sale lines from the last day of the month', function () {
    $this->travelTo(Carbon::parse('2026-08-31 20:00:00'));
    $this->actingAs(User::factory()->create(['activo' => true]));

    $product = Product::factory()->create(['nombre' => 'Producto Top']);
    $sale = Sale::factory()->create(['status' => 'confirmada', 'fecha' => now()]);
    SaleLine::factory()->create(['sale_id' => $sale->id, 'product_id' => $product->id, 'cantidad' => 10]);

    Livewire::test(ProductoMasVendidoWidget::class)
        ->assertSee('Producto Top');
});
