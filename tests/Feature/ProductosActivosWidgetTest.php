<?php

use App\Filament\Widgets\ProductosActivosWidget;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('it counts only active products', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Product::factory()->count(3)->create(['activo' => true]);
    Product::factory()->count(2)->create(['activo' => false]);

    Livewire::test(ProductosActivosWidget::class)
        ->assertSee('3');
});
