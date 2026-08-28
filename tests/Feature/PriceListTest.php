<?php

use App\Models\PriceList;
use App\Models\Product;

test('product price for a price list applies its percentage over costo_ultimo', function () {
    $product = Product::factory()->create(['costo_ultimo' => 1000]);
    $priceList = PriceList::factory()->create(['porcentaje' => 30]);

    expect($product->precioParaLista($priceList->id))->toEqual('1300.00');
});

test('product price for a price list with 0% equals costo_ultimo', function () {
    $product = Product::factory()->create(['costo_ultimo' => 1000]);
    $priceList = PriceList::factory()->create(['porcentaje' => 0]);

    expect($product->precioParaLista($priceList->id))->toEqual('1000.00');
});

test('product falls back to costo_ultimo without markup when no price list is given', function () {
    $product = Product::factory()->create(['costo_ultimo' => 1000]);

    expect($product->precioParaLista(null))->toEqual('1000.00');
});

test('a price list based on another applies its percentage over the referenced list price, not the cost', function () {
    $product = Product::factory()->create(['costo_ultimo' => 1000]);
    $mayorista = PriceList::factory()->create(['porcentaje' => 35]);
    $minorista = PriceList::factory()->create([
        'based_on_price_list_id' => $mayorista->id,
        'porcentaje' => -5,
    ]);

    // mayorista: 1000 * 1.35 = 1350; minorista: 1350 * 0.95 = 1282.50
    expect($product->precioParaLista($minorista->id))->toEqual('1282.50');
});

test('price list chains longer than two levels resolve correctly', function () {
    $product = Product::factory()->create(['costo_ultimo' => 1000]);
    $a = PriceList::factory()->create(['porcentaje' => 50]); // 1500
    $b = PriceList::factory()->create(['based_on_price_list_id' => $a->id, 'porcentaje' => -10]); // 1350
    $c = PriceList::factory()->create(['based_on_price_list_id' => $b->id, 'porcentaje' => 20]); // 1620

    expect($product->precioParaLista($c->id))->toEqual('1620.00');
});

test('a price list cannot be based on itself, directly or through a chain', function () {
    $a = PriceList::factory()->create();
    $b = PriceList::factory()->create(['based_on_price_list_id' => $a->id]);

    expect($a->wouldCreateCycle($a->id))->toBeTrue();
    expect($a->wouldCreateCycle($b->id))->toBeTrue();
    expect($b->wouldCreateCycle($a->id))->toBeFalse();
});

test('multiplicador compounds percentages across a chain', function () {
    $a = PriceList::factory()->create(['porcentaje' => 50]); // x1.5
    $b = PriceList::factory()->create(['based_on_price_list_id' => $a->id, 'porcentaje' => -10]); // x1.5 * x0.9 = x1.35
    $c = PriceList::factory()->create(['based_on_price_list_id' => $b->id, 'porcentaje' => 20]); // x1.35 * x1.2 = x1.62

    expect($a->multiplicador())->toEqual(1.5);
    expect($b->multiplicador())->toEqualWithDelta(1.35, 0.0001);
    expect($c->multiplicador())->toEqualWithDelta(1.62, 0.0001);
});

test('marking a price list as default unmarks the previous default', function () {
    $a = PriceList::factory()->create(['predeterminada' => true]);
    $b = PriceList::factory()->create(['predeterminada' => true]);

    expect($a->fresh()->predeterminada)->toBeFalse();
    expect($b->fresh()->predeterminada)->toBeTrue();
});

test('orderedForSharing only returns active price lists with sharing turned on', function () {
    $compartible = PriceList::factory()->create(['nombre' => 'Minorista', 'compartible' => true, 'activo' => true]);
    PriceList::factory()->create(['nombre' => 'Interna', 'compartible' => false, 'activo' => true]);
    PriceList::factory()->create(['nombre' => 'Descontinuada', 'compartible' => true, 'activo' => false]);

    expect(PriceList::orderedForSharing()->pluck('id')->all())->toEqual([$compartible->id]);
});

test('price lists are shareable by default', function () {
    $priceList = PriceList::create(['nombre' => 'Nueva', 'porcentaje' => 10]);

    expect($priceList->fresh()->compartible)->toBeTrue();
});
