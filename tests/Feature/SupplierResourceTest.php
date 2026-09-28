<?php

use App\Filament\Clusters\Partners\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Clusters\Partners\Resources\Suppliers\Pages\ListSuppliers;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('a new supplier is active by default', function () {
    Livewire::test(CreateSupplier::class)
        ->fillForm([
            'razon_social' => 'Proveedor Nuevo SA',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $supplier = Supplier::where('razon_social', 'Proveedor Nuevo SA')->firstOrFail();

    expect($supplier->activo)->toBeTrue();
});

test('the activo icon column toggles the supplier active status', function () {
    $supplier = Supplier::factory()->create(['activo' => true]);

    Livewire::test(ListSuppliers::class)
        ->callTableAction('toggleActivo', $supplier);

    expect($supplier->fresh()->activo)->toBeFalse();
});
