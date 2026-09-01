<?php

use App\Models\TimeEntry;
use App\Models\TimeEntrySettlement;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrativo', 'web');

    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();
    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(1)->subHours(3),
        'ended_at' => now()->subDays(1),
    ]);

    $this->settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());
});

test('an admin can download the settlement receipt pdf', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin)
        ->get(route('time-entry-settlements.receipt.pdf', $this->settlement))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('a non-admin cannot download the settlement receipt pdf', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get(route('time-entry-settlements.receipt.pdf', $this->settlement))
        ->assertForbidden();
});
