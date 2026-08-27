<?php

use App\Filament\Widgets\CompartirListaWidget;
use App\Models\PriceList;
use App\Models\User;
use Livewire\Livewire;

test('the widget links to share every active price list', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    $minorista = PriceList::factory()->create(['nombre' => 'Minorista', 'activo' => true]);
    $inactiva = PriceList::factory()->create(['nombre' => 'Descontinuada', 'activo' => false]);

    Livewire::test(CompartirListaWidget::class)
        ->assertSeeHtml(route('price-lists.pdf', $minorista))
        ->assertDontSeeHtml(route('price-lists.pdf', $inactiva));
});
