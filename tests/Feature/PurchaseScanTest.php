<?php

use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
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
