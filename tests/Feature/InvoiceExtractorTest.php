<?php

use App\Models\Product;
use App\Services\InvoiceExtractor;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-haiku-4-5']);

    $this->fakeImagePath = tempnam(sys_get_temp_dir(), 'invoice-test-').'.jpg';
    file_put_contents($this->fakeImagePath, 'fake-bytes');
});

afterEach(function () {
    if (isset($this->fakeImagePath) && file_exists($this->fakeImagePath)) {
        unlink($this->fakeImagePath);
    }
});

function fakeClaudeResponse(array $extracted): void
{
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => "```json\n".json_encode($extracted)."\n```"],
            ],
        ]),
    ]);
}

test('extract normalizes AR and US decimal formats without doing arithmetic', function () {
    $product = Product::factory()->create(['activo' => true]);

    fakeClaudeResponse([
        'proveedor' => 'Bebidas Andinas S.A.',
        'cuit' => '30-12345678-9',
        'tipo_comprobante' => 'factura_a',
        'punto_venta' => '0006',
        'numero' => '00008298',
        'fecha' => '2026-06-02',
        'vencimiento' => null,
        'subtotal' => '13.891,40',
        'iva' => '2.917,19',
        'total' => '16.808,59',
        'lineas' => [
            [
                'descripcion' => 'Aceite de girasol 1.5L',
                'cantidad' => '10',
                'unidad' => 'caja',
                'precio_unitario' => '1.389,14',
                'subtotal' => '13.891,40',
                'matched_product_id' => $product->id,
            ],
        ],
    ]);

    $result = app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');

    expect($result['subtotal'])->toBe(13891.40);
    expect($result['iva'])->toBe(2917.19);
    expect($result['total'])->toBe(16808.59);
    expect($result['numero'])->toBe('0006-00008298');
    expect($result['lineas'][0]['cantidad'])->toBe(10.0);
    expect($result['lineas'][0]['precio_unitario'])->toBe(1389.14);
    expect($result['lineas'][0]['unidad'])->toBe('caja');
    expect($result['lineas'][0]['matched_product_id'])->toBe($product->id);
    expect($result['lineas'][0]['consistente'])->toBeTrue();
});

test('extract parses US-style decimals (comma thousands, dot decimal)', function () {
    fakeClaudeResponse([
        'proveedor' => null, 'cuit' => null, 'tipo_comprobante' => null,
        'punto_venta' => null, 'numero' => null, 'fecha' => null, 'vencimiento' => null,
        'subtotal' => '1,148,785.38', 'iva' => null, 'total' => '1,148,785.38',
        'lineas' => [],
    ]);

    $result = app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');

    expect($result['subtotal'])->toBe(1148785.38);
});

test('extract discards a matched_product_id that does not belong to the active catalog', function () {
    fakeClaudeResponse([
        'proveedor' => null, 'cuit' => null, 'tipo_comprobante' => null,
        'punto_venta' => null, 'numero' => null, 'fecha' => null, 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null,
        'lineas' => [
            [
                'descripcion' => 'Producto inventado',
                'cantidad' => '1',
                'unidad' => 'u',
                'precio_unitario' => '100',
                'subtotal' => '100',
                'matched_product_id' => 999999,
            ],
        ],
    ]);

    $result = app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');

    expect($result['lineas'][0]['matched_product_id'])->toBeNull();
});

test('extract maps free-text units to the Product::base_unit enum values', function () {
    fakeClaudeResponse([
        'proveedor' => null, 'cuit' => null, 'tipo_comprobante' => null,
        'punto_venta' => null, 'numero' => null, 'fecha' => null, 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null,
        'lineas' => [
            ['descripcion' => 'A', 'cantidad' => '1', 'unidad' => 'bidones', 'precio_unitario' => '1', 'subtotal' => '1', 'matched_product_id' => null],
            ['descripcion' => 'B', 'cantidad' => '1', 'unidad' => 'Kilos', 'precio_unitario' => '1', 'subtotal' => '1', 'matched_product_id' => null],
            ['descripcion' => 'C', 'cantidad' => '1', 'unidad' => 'algo-raro', 'precio_unitario' => '1', 'subtotal' => '1', 'matched_product_id' => null],
        ],
    ]);

    $result = app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');

    expect($result['lineas'][0]['unidad'])->toBe('unidad');
    expect($result['lineas'][1]['unidad'])->toBe('kg');
    expect($result['lineas'][2]['unidad'])->toBe('unidad');
});

test('extract flags a line as inconsistent when quantity times price does not match the printed subtotal', function () {
    fakeClaudeResponse([
        'proveedor' => null, 'cuit' => null, 'tipo_comprobante' => null,
        'punto_venta' => null, 'numero' => null, 'fecha' => null, 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null,
        'lineas' => [
            ['descripcion' => 'A', 'cantidad' => '10', 'unidad' => 'u', 'precio_unitario' => '5', 'subtotal' => '9999', 'matched_product_id' => null],
        ],
    ]);

    $result = app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');

    expect($result['lineas'][0]['consistente'])->toBeFalse();
});

test('extract returns null fecha instead of guessing an implausible format', function () {
    fakeClaudeResponse([
        'proveedor' => null, 'cuit' => null, 'tipo_comprobante' => null,
        'punto_venta' => null, 'numero' => null, 'fecha' => '02/06/2026', 'vencimiento' => null,
        'subtotal' => null, 'iva' => null, 'total' => null, 'lineas' => [],
    ]);

    $result = app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');

    expect($result['fecha'])->toBeNull();
});

test('extract throws a friendly error when the API key is missing', function () {
    config(['services.anthropic.key' => null]);

    app(InvoiceExtractor::class)->extract($this->fakeImagePath, 'image/jpeg');
})->throws(RuntimeException::class, 'ANTHROPIC_API_KEY no está configurada.');
