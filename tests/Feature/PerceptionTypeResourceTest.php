<?php

use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages\CreatePerceptionType;
use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages\EditPerceptionType;
use App\Models\PerceptionType;
use App\Models\User;
use Livewire\Livewire;

test('an admin can create a perception type', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin);

    Livewire::test(CreatePerceptionType::class)
        ->fillForm([
            'nombre' => 'Percepción IIBB Buenos Aires',
            'porcentaje' => 4,
            'afecta_costo' => true,
            'activo' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $perceptionType = PerceptionType::where('nombre', 'Percepción IIBB Buenos Aires')->first();

    expect($perceptionType)->not->toBeNull();
    expect((float) $perceptionType->porcentaje)->toBe(4.0);
    expect($perceptionType->afecta_costo)->toBeTrue();
});

test('an admin can edit and delete a perception type', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $perceptionType = PerceptionType::factory()->create(['activo' => true, 'afecta_costo' => true]);

    $this->actingAs($admin);

    Livewire::test(EditPerceptionType::class, ['record' => $perceptionType->getRouteKey()])
        ->fillForm(['activo' => false, 'afecta_costo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($perceptionType->fresh()->activo)->toBeFalse();
    expect($perceptionType->fresh()->afecta_costo)->toBeFalse();

    $perceptionType->delete();

    expect(PerceptionType::query()->find($perceptionType->id))->toBeNull();
});

test('a vendedor cannot access the perception types resource', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/perception-types')
        ->assertForbidden();
});
