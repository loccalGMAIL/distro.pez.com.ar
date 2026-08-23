<?php

use App\Filament\Clusters\Catalog\Resources\Products\Pages\CreateProduct;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('a new product is active by default', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'sku' => 'SKU-TEST-001',
            'nombre' => 'Producto Nuevo',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('sku', 'SKU-TEST-001')->firstOrFail();

    expect($product->activo)->toBeTrue();
});
