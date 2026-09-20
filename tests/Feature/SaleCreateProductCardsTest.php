<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\CreateSale;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
    Customer::factory()->create(['predeterminado' => true]);
    PriceList::factory()->create(['predeterminada' => true]);
    Warehouse::factory()->create(['predeterminado' => true]);
});

test('tapping a product card opens a modal to enter the quantity, defaulting to 1', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);

    Livewire::test(CreateSale::class)
        ->callAction('addProductToSale', arguments: ['product' => $product->id])
        ->assertSchemaStateSet(function (array $state) use ($product) {
            $lines = collect($state['lines'])->filter(fn ($line) => is_array($line));

            expect($lines)->toHaveCount(1);
            expect((int) $lines->first()['product_id'])->toBe($product->id);
            expect((float) $lines->first()['cantidad'])->toBe(1.0);

            return [];
        });
});

test('entering a custom quantity in the modal adds the line with that quantity', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);

    Livewire::test(CreateSale::class)
        ->callAction('addProductToSale', data: ['cantidad' => 5], arguments: ['product' => $product->id])
        ->assertSchemaStateSet(function (array $state) {
            $lines = collect($state['lines'])->filter(fn ($line) => is_array($line));
            $line = $lines->first();

            expect($lines)->toHaveCount(1);
            expect((float) $line['cantidad'])->toBe(5.0);
            expect((float) $line['subtotal'])->toBe(round((float) $line['cantidad'] * (float) $line['precio_unit'], 2));

            return [];
        });
});

test('tapping the same product card twice sums the quantity instead of duplicating the line', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);

    Livewire::test(CreateSale::class)
        ->callAction('addProductToSale', data: ['cantidad' => 2], arguments: ['product' => $product->id])
        ->callAction('addProductToSale', data: ['cantidad' => 3], arguments: ['product' => $product->id])
        ->assertSchemaStateSet(function (array $state) {
            $lines = collect($state['lines'])->filter(fn ($line) => is_array($line));

            expect($lines)->toHaveCount(1);
            expect((float) $lines->first()['cantidad'])->toBe(5.0);

            return [];
        });
});

test('on mobile only the first six product cards are visible, the rest behind a "ver más" button', function () {
    $products = Product::factory()->count(8)->sequence(
        fn ($sequence) => ['nombre' => 'Producto '.chr(65 + $sequence->index)],
    )->create();

    $html = Livewire::test(CreateSale::class)->html();

    // Todas las cards se renderizan (en desktop se ven siempre; en mobile al desplegar).
    foreach ($products as $product) {
        expect($html)->toContain($product->nombre);
    }

    // Las que sobran quedan dentro del wrapper que arranca oculto por debajo de `sm`.
    expect($html)->toContain('hidden sm:contents');
    expect($html)->toContain('Ver más (2)');
});

test('the "ver más" button is not rendered when there are six products or fewer', function () {
    Product::factory()->count(6)->create();

    $html = Livewire::test(CreateSale::class)->html();

    expect($html)->not->toContain('hidden sm:contents');
    expect($html)->not->toContain('Ver más');
});

test('favorite products appear in a "Favoritos" section, the rest under "Todos los productos"', function () {
    $favorito = Product::factory()->create(['nombre' => 'Producto Estrella']);
    Product::factory()->create(['nombre' => 'Producto Comun']);

    $sale = Sale::factory()->create(['status' => 'confirmada']);
    SaleLine::factory()->create(['sale_id' => $sale->id, 'product_id' => $favorito->id, 'cantidad' => 8]);

    $html = Livewire::test(CreateSale::class)->html();

    expect($html)->toContain('Favoritos');
    expect($html)->toContain('Todos los productos');

    // El favorito se muestra dentro de la sección de favoritos: entre el
    // encabezado "Favoritos" y el encabezado "Todos los productos".
    expect(strpos($html, 'Producto Estrella'))->toBeGreaterThan(strpos($html, 'Favoritos'));
    expect(strpos($html, 'Producto Estrella'))->toBeLessThan(strpos($html, 'Todos los productos'));
    expect(strpos($html, 'Producto Comun'))->toBeGreaterThan(strpos($html, 'Todos los productos'));
});

test('the "Favoritos" section is not rendered when there are no confirmed sales', function () {
    Product::factory()->count(3)->create();

    $html = Livewire::test(CreateSale::class)->html();

    expect($html)->not->toContain('Favoritos');
    expect($html)->not->toContain('Todos los productos');
});
