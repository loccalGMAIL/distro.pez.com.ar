<?php

use App\Filament\Clusters\Settings\Resources\TimeEntrySettlements\Pages\ListTimeEntrySettlements;
use App\Models\Expense;
use App\Models\TimeEntry;
use App\Models\TimeEntrySettlement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Livewire\Livewire;

/**
 * Empleado con un ciclo de 4 horas ya liquidado.
 */
function liquidacionDePrueba(): TimeEntrySettlement
{
    $employee = User::factory()->create(['hourly_rate' => 1500, 'activo' => true]);
    $employee->assignRole('administrativo');

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(1)->subHours(4),
        'ended_at' => now()->subDays(1),
    ]);

    return TimeEntrySettlement::liquidar($employee, now()->toDateString());
}

beforeEach(function () {
    // Con los seeders reales, para que el test cubra el cableado de permisos
    // y no sólo la lógica del modelo.
    $this->seed(ShieldSeeder::class);
    $this->seed(RolePermissionSeeder::class);
});

test('an admin sees the settlement in the list', function () {
    $settlement = liquidacionDePrueba();

    $admin = User::factory()->create(['activo' => true]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/dashboard/settings/time-entry-settlements')
        ->assertSuccessful()
        ->assertSee($settlement->numero());
});

test('a legacy role cannot reach the settlements list', function (string $roleName) {
    $user = User::factory()->create(['activo' => true]);
    $user->assignRole($roleName);

    $this->actingAs($user)
        ->get('/dashboard/settings/time-entry-settlements')
        ->assertForbidden();
})->with(['vendedor', 'deposito', 'chofer']);

test('anular from the list returns the cycles to pending and drops the expense', function () {
    $settlement = liquidacionDePrueba();
    $expenseId = $settlement->expense_id;

    $admin = User::factory()->create(['activo' => true]);
    $admin->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(ListTimeEntrySettlements::class)
        ->callTableAction('anular', $settlement);

    expect($settlement->fresh()->status)->toBe('anulada')
        ->and(Expense::query()->whereKey($expenseId)->exists())->toBeFalse()
        ->and(TimeEntrySettlement::pendientes($settlement->user_id)->count())->toBe(1);
});

test('an already anulada settlement does not offer the anular action again', function () {
    $settlement = liquidacionDePrueba();
    $settlement->anular();

    $admin = User::factory()->create(['activo' => true]);
    $admin->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(ListTimeEntrySettlements::class)
        ->assertTableActionHidden('anular', $settlement);
});
