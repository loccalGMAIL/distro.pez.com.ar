<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

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
