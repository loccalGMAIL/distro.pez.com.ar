<?php

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('the purchase archivo route requires authentication', function () {
    $purchase = Purchase::factory()->create(['archivo_path' => 'purchases/factura.jpg']);

    $this->get(route('purchases.archivo', $purchase))->assertRedirect();
});

test('the purchase archivo route 404s when there is no file', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    $purchase = Purchase::factory()->create(['archivo_path' => null]);

    $this->get(route('purchases.archivo', $purchase))->assertNotFound();
});

test('the purchase archivo route streams the private file for an authenticated user', function () {
    Storage::fake('local');
    Storage::disk('local')->put('purchases/factura.jpg', 'contenido-de-prueba');

    $this->actingAs(User::factory()->create(['activo' => true]));

    $purchase = Purchase::factory()->create(['archivo_path' => 'purchases/factura.jpg']);

    $this->get(route('purchases.archivo', $purchase))->assertOk();

    expect(Storage::disk('local')->get('purchases/factura.jpg'))->toBe('contenido-de-prueba');
});
