<?php

use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Models\PerceptionType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPerceptionLink;
use App\Models\SupplierProductLink;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['activo' => true]));
    config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-haiku-4-5']);
    Storage::fake('local');
    Warehouse::factory()->create(['predeterminado' => true]);
});

function fakeClaudeExtraction(array $extracted): void
{
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => json_encode($extracted)],
            ],
        ]),
    ]);
}

test('the scan page renders for an active user', function () {
    Livewire::test(ScanPurchase::class)->assertSuccessful();
});

test('capturing an invoice extracts data and prefills the review step', function () {
    $supplier = Supplier::factory()->create(['razon_social' => 'Bebidas Andinas S.A.', 'activo' => true]);
    $product = Product::factory()->create(['activo' => true, 'costo_ultimo' => 100]);

    fakeClaudeExtraction([
        'proveedor' => 'Bebidas Andinas S.A.',
        'cuit' => null,
        'tipo_comprobante' => 'factura_a',
        'punto_venta' => '0001',
        'numero' => '00000042',
        'fecha' => '2026-06-02',
        'vencimiento' => null,
        'subtotal' => '1000',
        'iva' => '210',
        'total' => '1210',
        'lineas' => [
            [
                'descripcion' => 'Producto de prueba',
                'cantidad' => '10',
                'unidad' => 'u',
                'precio_unitario' => '100',
                'subtotal' => '1000',
                'matched_product_id' => $product->id,
            ],
        ],
    ]);

    Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->assertHasNoErrors()
        ->assertSet('data.supplier_id', $supplier->id)
        ->assertSet('data.numero', '0001-00000042')
        ->assertSet('data.fecha', '2026-06-02');
});

test('confirming after a scan creates a draft purchase with its lines and remembers the product link', function () {
    $supplier = Supplier::factory()->create(['razon_social' => 'Bebidas Andinas S.A.', 'activo' => true]);
    $product = Product::factory()->create(['activo' => true, 'costo_ultimo' => 100]);

    fakeClaudeExtraction([
        'proveedor' => 'Bebidas Andinas S.A.',
        'cuit' => null,
        'tipo_comprobante' => 'factura_a',
        'punto_venta' => '0001',
        'numero' => '00000042',
        'fecha' => '2026-06-02',
        'vencimiento' => null,
        'subtotal' => '1000',
        'iva' => '210',
        'total' => '1210',
        'lineas' => [
            [
                'descripcion' => 'Producto de prueba',
                'cantidad' => '10',
                'unidad' => 'u',
                'precio_unitario' => '100',
                'subtotal' => '1000',
                'matched_product_id' => $product->id,
            ],
        ],
    ]);

    Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->call('confirmar');

    $purchase = Purchase::first();

    expect($purchase)->not->toBeNull();
    expect($purchase->status)->toBe('borrador');
    expect($purchase->supplier_id)->toBe($supplier->id);
    expect($purchase->numero)->toBe('0001-00000042');
    expect($purchase->archivo_path)->not->toBeNull();
    expect($purchase->ocr_data)->not->toBeNull();
    expect($purchase->lines)->toHaveCount(1);
    expect((float) $purchase->lines->first()->cantidad)->toBe(10.0);
    expect((float) $purchase->lines->first()->costo_unit)->toBe(100.0);

    expect(SupplierProductLink::where('supplier_id', $supplier->id)->where('product_id', $product->id)->exists())->toBeTrue();
});

test('capturing an invoice with a percepcion prefills the perceptions repeater', function () {
    Supplier::factory()->create(['razon_social' => 'Bebidas Andinas S.A.', 'activo' => true]);
    $product = Product::factory()->create(['activo' => true, 'costo_ultimo' => 100]);
    $perceptionType = PerceptionType::factory()->create(['nombre' => 'Percepción IIBB Buenos Aires', 'activo' => true]);

    fakeClaudeExtraction([
        'proveedor' => 'Bebidas Andinas S.A.',
        'cuit' => null,
        'tipo_comprobante' => 'factura_a',
        'punto_venta' => '0001',
        'numero' => '00000042',
        'fecha' => '2026-06-02',
        'vencimiento' => null,
        'subtotal' => '1000',
        'iva' => '210',
        'total' => '1360',
        'lineas' => [
            [
                'descripcion' => 'Producto de prueba',
                'cantidad' => '10',
                'unidad' => 'u',
                'precio_unitario' => '100',
                'subtotal' => '1000',
                'matched_product_id' => $product->id,
            ],
        ],
        'percepciones' => [
            [
                'descripcion' => 'Perc. IIBB Bs As',
                'monto' => '150',
                'matched_perception_type_id' => $perceptionType->id,
            ],
        ],
    ]);

    Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->assertHasNoErrors()
        ->assertSet('data.perceptions.0.perception_type_id', $perceptionType->id)
        ->assertSet('data.perceptions.0.monto', 150.0)
        ->assertSet('data.percepciones', 150.0);
});

test('confirming after a scan with a percepcion creates a PurchasePerception and remembers the link', function () {
    $supplier = Supplier::factory()->create(['razon_social' => 'Bebidas Andinas S.A.', 'activo' => true]);
    $product = Product::factory()->create(['activo' => true, 'costo_ultimo' => 100]);
    $perceptionType = PerceptionType::factory()->create(['nombre' => 'Percepción IIBB Buenos Aires', 'activo' => true]);

    fakeClaudeExtraction([
        'proveedor' => 'Bebidas Andinas S.A.',
        'cuit' => null,
        'tipo_comprobante' => 'factura_a',
        'punto_venta' => '0001',
        'numero' => '00000042',
        'fecha' => '2026-06-02',
        'vencimiento' => null,
        'subtotal' => '1000',
        'iva' => '210',
        'total' => '1360',
        'lineas' => [
            [
                'descripcion' => 'Producto de prueba',
                'cantidad' => '10',
                'unidad' => 'u',
                'precio_unitario' => '100',
                'subtotal' => '1000',
                'matched_product_id' => $product->id,
            ],
        ],
        'percepciones' => [
            [
                'descripcion' => 'Perc. IIBB Bs As',
                'monto' => '150',
                'matched_perception_type_id' => $perceptionType->id,
            ],
        ],
    ]);

    Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->call('confirmar');

    $purchase = Purchase::first();

    expect($purchase)->not->toBeNull();
    expect($purchase->perceptions)->toHaveCount(1);
    expect((float) $purchase->perceptions->first()->monto)->toBe(150.0);
    expect($purchase->perceptions->first()->perception_type_id)->toBe($perceptionType->id);
    expect((float) $purchase->percepciones)->toBe(150.0);
    expect((float) $purchase->total)->toBe(1000.0 - 0.0 + 210.0 + 150.0);

    expect(SupplierPerceptionLink::where('supplier_id', $supplier->id)->where('perception_type_id', $perceptionType->id)->exists())->toBeTrue();
});

test('when no supplier matches, extraction suggests creating one prefilled with the detected name', function () {
    $product = Product::factory()->create(['activo' => true, 'costo_ultimo' => 100]);

    fakeClaudeExtraction([
        'proveedor' => 'Distribuidora Nueva S.A.',
        'cuit' => '30-12345678-9',
        'tipo_comprobante' => 'factura_a',
        'punto_venta' => '0001',
        'numero' => '00000001',
        'fecha' => '2026-06-02',
        'vencimiento' => null,
        'subtotal' => null,
        'iva' => null,
        'total' => null,
        'lineas' => [
            [
                'descripcion' => 'Producto de prueba',
                'cantidad' => '1',
                'unidad' => 'u',
                'precio_unitario' => '10',
                'subtotal' => '10',
                'matched_product_id' => $product->id,
            ],
        ],
    ]);

    $component = Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->assertNotified('No encontramos al proveedor "Distribuidora Nueva S.A."')
        ->assertSet('data.supplier_id', null);

    $component->mountFormComponentAction('supplier_id', 'createOption')
        ->assertFormComponentActionDataSet([
            'razon_social' => 'Distribuidora Nueva S.A.',
            'cuit' => '30-12345678-9',
        ]);

    $component->callMountedFormComponentAction()
        ->assertHasNoFormComponentActionErrors();

    $newSupplier = Supplier::where('razon_social', 'Distribuidora Nueva S.A.')->first();

    expect($newSupplier)->not->toBeNull();
    expect($newSupplier->cuit)->toBe('30-12345678-9');
    $component->assertSet('data.supplier_id', $newSupplier->id);
});

test('when a line has no product match, extraction suggests creating one prefilled from the line', function () {
    Supplier::factory()->create(['razon_social' => 'Bebidas Andinas S.A.', 'activo' => true]);

    fakeClaudeExtraction([
        'proveedor' => 'Bebidas Andinas S.A.', 'cuit' => null, 'tipo_comprobante' => 'factura_a',
        'punto_venta' => null, 'numero' => null, 'fecha' => '2026-06-02', 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null,
        'lineas' => [
            [
                'descripcion' => 'Fideos Matarazzo 500g',
                'cantidad' => '5',
                'unidad' => 'kg',
                'precio_unitario' => '250',
                'subtotal' => '1250',
                'matched_product_id' => null,
            ],
        ],
    ]);

    $component = Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->assertNotified('Hay 1 línea sin producto detectado')
        ->assertSet('data.lineas.0.product_id', null);

    $component->mountFormComponentAction('lineas.0.product_id', 'createOption')
        ->assertFormComponentActionDataSet([
            'nombre' => 'Fideos Matarazzo 500g',
            'base_unit' => 'kg',
            'costo_ultimo' => 250,
        ]);

    $component->callMountedFormComponentAction()
        ->assertHasNoFormComponentActionErrors();

    $newProduct = Product::where('nombre', 'Fideos Matarazzo 500g')->first();

    expect($newProduct)->not->toBeNull();
    expect($newProduct->base_unit)->toBe('kg');
    expect((float) $newProduct->costo_ultimo)->toBe(250.0);
    $component->assertSet('data.lineas.0.product_id', (string) $newProduct->id);
});

test('when a percepcion has no match, extraction suggests creating one prefilled with the detected description', function () {
    Supplier::factory()->create(['razon_social' => 'Bebidas Andinas S.A.', 'activo' => true]);
    $product = Product::factory()->create(['activo' => true, 'costo_ultimo' => 100]);

    fakeClaudeExtraction([
        'proveedor' => 'Bebidas Andinas S.A.', 'cuit' => null, 'tipo_comprobante' => 'factura_a',
        'punto_venta' => null, 'numero' => null, 'fecha' => '2026-06-02', 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null,
        'lineas' => [
            [
                'descripcion' => 'Producto de prueba',
                'cantidad' => '1',
                'unidad' => 'u',
                'precio_unitario' => '100',
                'subtotal' => '100',
                'matched_product_id' => $product->id,
            ],
        ],
        'percepciones' => [
            [
                'descripcion' => 'Perc. IIBB Bs As RG 3337',
                'monto' => '150',
                'matched_perception_type_id' => null,
            ],
        ],
    ]);

    $component = Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->assertSet('data.perceptions.0.perception_type_id', null);

    $component->mountFormComponentAction('perceptions.0.perception_type_id', 'createOption')
        ->assertFormComponentActionDataSet([
            'nombre' => 'Perc. IIBB Bs As RG 3337',
        ]);

    $component->callMountedFormComponentAction()
        ->assertHasNoFormComponentActionErrors();

    $newPerceptionType = PerceptionType::where('nombre', 'Perc. IIBB Bs As RG 3337')->first();

    expect($newPerceptionType)->not->toBeNull();
    $component->assertSet('data.perceptions.0.perception_type_id', (string) $newPerceptionType->id);
});

test('confirming without any line matched to a product shows an error and creates nothing', function () {
    Supplier::factory()->create(['razon_social' => 'Proveedor Sin Match', 'activo' => true]);

    fakeClaudeExtraction([
        'proveedor' => 'Proveedor Sin Match', 'cuit' => null, 'tipo_comprobante' => 'factura_a',
        'punto_venta' => null, 'numero' => null, 'fecha' => '2026-06-02', 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null,
        'lineas' => [
            [
                'descripcion' => 'Producto que no matchea con nada',
                'cantidad' => '1',
                'unidad' => 'u',
                'precio_unitario' => '10',
                'subtotal' => '10',
                'matched_product_id' => null,
            ],
        ],
    ]);

    Livewire::test(ScanPurchase::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('factura.jpg', 800, 600)])
        ->goToNextWizardStep()
        ->call('confirmar')
        ->assertNotified();

    expect(Purchase::count())->toBe(0);
});
