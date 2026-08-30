<?php

use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\EditPurchase;
use App\Models\PerceptionType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePerception;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('editing a purchase persists a percepcion row and its contribution to the cached total', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);
    $perceptionType = PerceptionType::factory()->create(['activo' => true]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'descuento' => 0]);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 100,
        'subtotal' => 100,
    ]);

    Livewire::test(EditPurchase::class, ['record' => $purchase->getRouteKey()])
        ->fillForm([
            'perceptions' => [
                ['perception_type_id' => $perceptionType->id, 'descripcion' => 'Perc test', 'monto' => 50],
            ],
            'percepciones' => 50,
            'total' => 150,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $purchase->refresh();

    expect((float) $purchase->percepciones)->toBe(50.0);
    expect((float) $purchase->total)->toBe(150.0);
    expect($purchase->perceptions)->toHaveCount(1);
    expect($purchase->perceptions->first()->perception_type_id)->toBe($perceptionType->id);
});

test('loading an existing percepcion displays its raw decimal in the money mask format, not corrupted', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);
    $perceptionType = PerceptionType::factory()->create(['activo' => true]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'descuento' => 1234.5]);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 100,
        'subtotal' => 100,
    ]);
    $perception = PurchasePerception::factory()->for($purchase)->create([
        'perception_type_id' => $perceptionType->id,
        'monto' => 18060,
    ]);

    $key = 'record-'.$perception->getKey();

    Livewire::test(EditPurchase::class, ['record' => $purchase->getRouteKey()])
        ->assertSet("data.perceptions.{$key}.monto", '18.060,00')
        ->assertSet('data.descuento', '1.234,50');
});

test('re-editing and re-saving an already-saved percepcion does not multiply its value', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);
    $perceptionType = PerceptionType::factory()->create(['activo' => true]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'descuento' => 0]);
    PurchaseLine::factory()->for($purchase)->create([
        'product_id' => $product->id,
        'cantidad' => 1,
        'costo_unit' => 100,
        'subtotal' => 100,
    ]);
    $perception = PurchasePerception::factory()->for($purchase)->create([
        'perception_type_id' => $perceptionType->id,
        'monto' => 18.06,
    ]);

    $key = 'record-'.$perception->getKey();

    // "18.060" is exactly what the $money mask leaves in the input after the
    // user types the digits "18060" with no comma (no cents typed) — the
    // regression this guards against: re-hydrating/re-saving that text must
    // not reinterpret it as 18.06 again.
    Livewire::test(EditPurchase::class, ['record' => $purchase->getRouteKey()])
        ->fillForm([
            'perceptions' => [
                $key => ['monto' => '18.060'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $perception->fresh()->monto)->toBe(18060.0);
    expect((float) $purchase->fresh()->percepciones)->toBe(18060.0);
    expect((float) $purchase->fresh()->total)->toBe(18160.0);
});
