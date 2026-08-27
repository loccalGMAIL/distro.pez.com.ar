<?php

use App\Filament\Clusters\Catalog\Resources\Products\ProductResource;
use App\Filament\Widgets\ProductosWidget;
use App\Models\User;
use Livewire\Livewire;

test('the widget links to the products list page', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Livewire::test(ProductosWidget::class)
        ->assertSeeHtml(ProductResource::getUrl());
});
