<?php

use App\Filament\Widgets\FichajeWidget;
use App\Filament\Widgets\ProductosWidget;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(ShieldSeeder::class);
    $this->seed(RolePermissionSeeder::class);
});

test('a role without the widget permission does not see it on the dashboard', function () {
    $user = User::factory()->create(['activo' => true]);
    $user->assignRole(Role::findOrCreate('sin_permisos', 'web'));

    $this->actingAs($user);

    expect(ProductosWidget::canView())->toBeFalse();
});

test('Dueño sees the newly gated dashboard widgets by default', function () {
    $dueño = User::factory()->withRole('Dueño')->create(['activo' => true]);

    $this->actingAs($dueño);

    expect(ProductosWidget::canView())->toBeTrue();
});

test('a legacy role keeps seeing the newly gated widgets via the wildcard grant', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor);

    expect(ProductosWidget::canView())->toBeTrue();
});

test('administrativo only sees the fichaje widget, not the newly gated ones', function () {
    $administrativo = User::factory()->administrativo()->create(['activo' => true]);

    $this->actingAs($administrativo);

    expect(FichajeWidget::canView())->toBeTrue()
        ->and(ProductosWidget::canView())->toBeFalse();
});
