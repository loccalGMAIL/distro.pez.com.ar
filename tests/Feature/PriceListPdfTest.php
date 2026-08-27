<?php

use App\Models\PriceList;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('viewing the price list pdf route streams a PDF', function () {
    $priceList = PriceList::factory()->create(['nombre' => 'Mayorista']);
    Product::factory()->create(['costo_ultimo' => 1000]);

    $this->get(route('price-lists.pdf', $priceList))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('the products view shows a link to share every active price list', function () {
    $minorista = PriceList::factory()->create(['nombre' => 'Minorista', 'activo' => true]);
    $inactiva = PriceList::factory()->create(['nombre' => 'Descontinuada', 'activo' => false]);

    $html = $this->get('/dashboard/catalog/products')->getContent();

    expect($html)->toContain(route('price-lists.pdf', $minorista))
        ->and($html)->not->toContain(route('price-lists.pdf', $inactiva));
});

test('the mobile navbar compartir button is available on every panel page', function () {
    $minorista = PriceList::factory()->create(['nombre' => 'Minorista', 'activo' => true]);

    $html = $this->get('/dashboard/partners/suppliers')->getContent();

    expect($html)->toContain(route('price-lists.pdf', $minorista));
});
