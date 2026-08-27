<?php

use App\Filament\Clusters\Catalog\Resources\Products\Pages\ListProducts;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\User;
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
