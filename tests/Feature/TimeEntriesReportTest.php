<?php

use App\Filament\Clusters\Settings\Pages\TimeEntriesReport;
use App\Models\TimeEntry;
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
