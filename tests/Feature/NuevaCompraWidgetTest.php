<?php

use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Filament\Widgets\NuevaCompraWidget;
use App\Models\User;
use Livewire\Livewire;

test('the widget links directly to the invoice scanner', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Livewire::test(NuevaCompraWidget::class)
        ->assertSeeHtml(ScanPurchase::getUrl());
});
