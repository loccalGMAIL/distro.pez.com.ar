<?php

use App\Models\TimeEntry;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrativo', 'web');
});

test('an admin can download the fichajes report pdf', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();
    TimeEntry::factory()->for($employee)->closed()->create();

    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin)
        ->get(route('time-entries.report.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('a non-admin cannot download the fichajes report pdf', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get(route('time-entries.report.pdf'))
        ->assertForbidden();
});
