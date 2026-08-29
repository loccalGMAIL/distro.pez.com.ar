<?php

use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\EditPurchase;
use App\Models\PerceptionType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('editing a purchase persists a percepcion row and its contribution to the cached total', function () {
    $product = Product::factory()->create(['costo_ultimo' => 100]);
    $perceptionType = PerceptionType::factory()->create(['activo' => true]);
    $purchase = Purchase::factory()->create(['status' => 'borrador', 'descuento' => 0, 'iva' => 0]);
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
