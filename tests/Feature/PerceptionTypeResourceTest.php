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
            'activo' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(PerceptionType::where('nombre', 'Percepción IIBB Buenos Aires')->exists())->toBeTrue();
});

test('an admin can edit and delete a perception type', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $perceptionType = PerceptionType::factory()->create(['activo' => true]);

    $this->actingAs($admin);

    Livewire::test(EditPerceptionType::class, ['record' => $perceptionType->getRouteKey()])
        ->fillForm(['activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($perceptionType->fresh()->activo)->toBeFalse();

    $perceptionType->delete();

    expect(PerceptionType::query()->find($perceptionType->id))->toBeNull();
});

test('a vendedor cannot access the perception types resource', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/perception-types')
        ->assertForbidden();
});
