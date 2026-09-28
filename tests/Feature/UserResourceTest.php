<?php

use App\Filament\Clusters\Settings\Resources\Users\Pages\CreateUser;
use App\Filament\Clusters\Settings\Resources\Users\Pages\EditUser;
use App\Filament\Clusters\Settings\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('creating a user assigns the selected role', function () {
    $vendedor = Role::findByName('vendedor', 'web');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Nueva Vendedora',
            'email' => 'vendedora@example.com',
            'password' => 'password123',
            'roles' => [$vendedor->id],
            'activo' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'vendedora@example.com')->firstOrFail();

    expect($user->hasRole('vendedor'))->toBeTrue();
});

test('editing a user can change its role and keep the password unchanged', function () {
    $vendedor = Role::findByName('vendedor', 'web');
    $deposito = Role::findByName('deposito', 'web');

    $user = User::factory()->withRole('vendedor')->create(['activo' => true]);
    $originalPassword = $user->password;

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'roles' => [$deposito->id],
            'password' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->hasRole('deposito'))->toBeTrue()
        ->and($user->hasRole('vendedor'))->toBeFalse()
        ->and($user->password)->toBe($originalPassword);
});

test('the activo icon column toggles another user active status with confirmation', function () {
    $user = User::factory()->withRole('vendedor')->create(['activo' => true]);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('toggleActivo')->table($user));

    expect($user->fresh()->activo)->toBeFalse();
});

test('a user cannot deactivate itself from the list', function () {
    $admin = auth()->user();

    Livewire::test(ListUsers::class)
        ->assertActionDisabled(TestAction::make('toggleActivo')->table($admin));

    expect($admin->fresh()->activo)->toBeTrue();
});
