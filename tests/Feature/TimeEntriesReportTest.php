<?php

use App\Filament\Clusters\Settings\Pages\TimeEntriesReport;
use App\Models\TimeEntry;
use App\Models\TimeEntrySettlement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrativo', 'web');
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('the report page can be rendered by an admin', function () {
    Livewire::test(TimeEntriesReport::class)->assertSuccessful();
});

test('a vendedor cannot access the report page', function () {
    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/time-entries-report')
        ->assertForbidden();
});

test('open entries are excluded from the summary', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subHours(2),
        'ended_at' => null,
    ]);

    $summary = Livewire::test(TimeEntriesReport::class)->instance()->summaryRows();

    expect($summary)->toBeEmpty();
});

test('the summary totals hours and pay per employee for closed cycles', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(1)->subHours(3),
        'ended_at' => now()->subDays(1),
    ]);
    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(2)->subHours(2),
        'ended_at' => now()->subDays(2),
    ]);

    $summary = Livewire::test(TimeEntriesReport::class)->instance()->summaryRows();

    expect($summary)->toHaveCount(1)
        ->and($summary->first()['hours'])->toBe(5.0)
        ->and($summary->first()['pay'])->toBe(5000.0);
});

test('filtering by period only sums entries within that range', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();

    $inRange = TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(5)->subHours(2),
        'ended_at' => now()->subDays(5),
    ]);
    $outOfRange = TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(40)->subHours(2),
        'ended_at' => now()->subDays(40),
    ]);

    $component = Livewire::test(TimeEntriesReport::class)
        ->set('tableFilters.periodo.desde', now()->subDays(10)->toDateString())
        ->set('tableFilters.periodo.hasta', now()->toDateString());

    $summary = $component->instance()->summaryRows();

    expect($summary)->toHaveCount(1)
        ->and($summary->first()['hours'])->toBe(2.0);
});

test('the settlement filter separates pending from settled cycles', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(3)->subHours(3),
        'ended_at' => now()->subDays(3),
    ]);

    TimeEntrySettlement::liquidar($employee, now()->subDays(2)->toDateString());

    // Ciclo nuevo, posterior a la liquidación: queda pendiente.
    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subHours(2),
        'ended_at' => now(),
    ]);

    $pendientes = Livewire::test(TimeEntriesReport::class)
        ->set('tableFilters.liquidacion.value', 'pendientes')
        ->instance()
        ->summaryRows();

    $liquidados = Livewire::test(TimeEntriesReport::class)
        ->set('tableFilters.liquidacion.value', 'liquidados')
        ->instance()
        ->summaryRows();

    expect($pendientes->first()['hours'])->toBe(2.0)
        ->and($liquidados->first()['hours'])->toBe(3.0);
});

test('the report defaults to showing only pending cycles', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(3)->subHours(3),
        'ended_at' => now()->subDays(3),
    ]);

    TimeEntrySettlement::liquidar($employee, now()->toDateString());

    Livewire::test(TimeEntriesReport::class)
        ->assertSuccessful()
        ->assertSet('tableFilters.liquidacion.value', 'pendientes');
});

test('an admin sees the liquidar action and a vendedor does not reach the page', function () {
    Livewire::test(TimeEntriesReport::class)->assertActionVisible('liquidar');

    $vendedor = User::factory()->vendedor()->create(['activo' => true]);

    $this->actingAs($vendedor)
        ->get('/dashboard/settings/time-entries-report')
        ->assertForbidden();
});

test('liquidar from the report registers the settlement and zeroes the counter', function () {
    $employee = User::factory()->administrativo()->hourlyRate(1000)->create();

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(1)->subHours(4),
        'ended_at' => now()->subDays(1),
    ]);

    Livewire::test(TimeEntriesReport::class)
        ->callAction('liquidar', [
            'user_id' => $employee->id,
            'hasta' => now()->toDateString(),
            'fecha_pago' => now()->toDateString(),
            'medio_pago' => 'transferencia',
        ])
        ->assertHasNoActionErrors();

    $settlement = TimeEntrySettlement::query()->firstOrFail();

    expect((float) $settlement->total)->toBe(4000.0)
        ->and($settlement->medio_pago)->toBe('transferencia')
        ->and(Livewire::test(TimeEntriesReport::class)->instance()->summaryRows())->toBeEmpty();
});
