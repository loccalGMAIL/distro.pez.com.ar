<?php

use App\Filament\Clusters\Partners\Resources\Customers\CustomerResource;
use App\Filament\Widgets\NuevaClienteWidget;
use App\Models\User;
use Livewire\Livewire;

test('the widget links to the customer create page', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Livewire::test(NuevaClienteWidget::class)
        ->assertSeeHtml(CustomerResource::getUrl('create'));
});
