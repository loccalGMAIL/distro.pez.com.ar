<?php

use App\Filament\Clusters\Settings\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Filament\Clusters\Settings\Resources\TimeEntries\Pages\EditTimeEntry;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrativo', 'web');
});

test('an admin can create a time entry for an employee', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $employee = User::factory()->administrativo()->create(['activo' => true]);

    $this->actingAs($admin);

    Livewire::test(CreateTimeEntry::class)
        ->fillForm([
            'user_id' => $employee->id,
            'started_at' => now()->subHours(3),
            'ended_at' => now(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TimeEntry::query()->where('user_id', $employee->id)->exists())->toBeTrue();
});

test('an admin can edit and delete a time entry', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $entry = TimeEntry::factory()->for(User::factory()->administrativo())->closed()->create();

    $this->actingAs($admin);

    Livewire::test(EditTimeEntry::class, ['record' => $entry->getRouteKey()])
        ->fillForm(['ended_at' => $entry->started_at->addHour()])
        ->call('save')
        ->assertHasNoFormErrors();

    $entry->delete();

    expect(TimeEntry::query()->find($entry->id))->toBeNull();
});

test('a vendedor cannot access the time entries resource', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/time-entries')
        ->assertForbidden();
});

test('an administrativo user cannot access the time entries resource', function () {
    $employee = User::factory()->administrativo()->create(['activo' => true]);

    $this->actingAs($employee)
        ->get('/dashboard/settings/time-entries')
        ->assertForbidden();
});
