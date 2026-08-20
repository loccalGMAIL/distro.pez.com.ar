<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\ListSales;
use App\Models\CompanySetting;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['activo' => true]));
});

test('a draft sale has no comprobante numero', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);

    expect($sale->comprobanteNumero())->toBeNull();
});

test('confirming a sale makes the comprobante numero available', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);
    SaleLine::factory()->create(['sale_id' => $sale->id]);

    $sale->confirmar();

    expect($sale->fresh()->comprobanteNumero())->not->toBeNull();
});

test('the comprobante numero combines punto_venta and the sale numero', function () {
    CompanySetting::factory()->create(['punto_venta' => '0001']);

    $sale = Sale::factory()->create(['status' => 'confirmada', 'numero' => '00000042']);

    expect($sale->comprobanteNumero())->toBe('0001-00000042');
});

test('viewing the comprobante route streams a PDF for a confirmed sale', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);
    SaleLine::factory()->create(['sale_id' => $sale->id]);
    $sale->confirmar();

    $this->get(route('sales.comprobante', $sale))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('the comprobante route 404s for a draft sale', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);

    $this->get(route('sales.comprobante', $sale))->assertNotFound();
});

test('the ver comprobante action is only visible once the sale is confirmed', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);
    SaleLine::factory()->create(['sale_id' => $sale->id]);

    Livewire::test(ListSales::class)
        ->assertTableActionHidden('verComprobante', $sale);

    $sale->confirmar();

    Livewire::test(ListSales::class)
        ->assertTableActionVisible('verComprobante', $sale->fresh());
});

test('confirming a sale from the table sends a notification with a link to the comprobante', function () {
    $sale = Sale::factory()->create(['status' => 'borrador']);
    SaleLine::factory()->create(['sale_id' => $sale->id]);

    Livewire::test(ListSales::class)
        ->callTableAction('confirmar', $sale)
        ->assertNotified("Venta {$sale->numero} confirmada");
});
