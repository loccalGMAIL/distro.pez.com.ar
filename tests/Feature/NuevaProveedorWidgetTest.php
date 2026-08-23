<?php

use App\Filament\Clusters\Partners\Resources\Suppliers\SupplierResource;
use App\Filament\Widgets\NuevaProveedorWidget;
use App\Models\User;
use Livewire\Livewire;

test('the widget links to the supplier create page', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Livewire::test(NuevaProveedorWidget::class)
        ->assertSeeHtml(SupplierResource::getUrl('create'));
});
