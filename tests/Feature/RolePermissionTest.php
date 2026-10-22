<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('legacy roles do not inherit fichaje permissions from the wildcard grant', function () {
    $this->seed(ShieldSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    foreach (['deposito', 'chofer'] as $roleName) {
        $permissions = Role::findByName($roleName, 'web')->permissions->pluck('name');

        expect($permissions->filter(fn (string $name): bool => str_contains($name, 'TimeEntry')
            || $name === 'View:FichajeWidget'
            || $name === 'View:TimeEntriesReport'))->toBeEmpty();
    }

    expect(Role::findByName('administrativo', 'web')->permissions->pluck('name')->all())
        ->toBe(['View:FichajeWidget']);
});

test('the vendedor role has an explicit scoped permission list', function () {
    $this->seed(ShieldSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    $permissions = Role::findByName('vendedor', 'web')->permissions->pluck('name')->all();

    expect($permissions)->toEqualCanonicalizing([
        'ViewAny:Sale', 'View:Sale', 'Create:Sale', 'Update:Sale',
        'ViewAny:Customer', 'View:Customer', 'Create:Customer', 'Update:Customer',
        'View:SalesCluster', 'View:PartnersCluster', 'View:NuevaVentaWidget', 'View:CompartirListaWidget',
    ]);
});

test('a vendedor without permission cannot access user management', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/users')
        ->assertForbidden();
});

test('an admin can access user management', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin)
        ->get('/dashboard/settings/users')
        ->assertSuccessful();
});

test('a vendedor with the resource permission granted by an admin can access it', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);
    $vendedor->givePermissionTo(Permission::findOrCreate('ViewAny:User', 'web'));

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/users')
        ->assertSuccessful();
});
