<?php

use App\Filament\Clusters\Settings\Pages\TimeEntriesReport;
use App\Models\Expense;
use App\Models\TimeEntry;
use App\Models\TimeEntrySettlement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrativo', 'web');
});

/**
 * Empleado con tarifa y dos ciclos cerrados de 3 y 2 horas (5 en total).
 */
function empleadoConCiclos(float $tarifa = 1000): User
{
    $employee = User::factory()->administrativo()->hourlyRate($tarifa)->create();

    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(2)->subHours(3),
        'ended_at' => now()->subDays(2),
    ]);
    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subDays(1)->subHours(2),
        'ended_at' => now()->subDays(1),
    ]);

    return $employee;
}

test('liquidar congela horas, tarifa y total, y marca los ciclos incluidos', function () {
    $employee = empleadoConCiclos();

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());

    expect((float) $settlement->horas)->toBe(5.0)
        ->and((float) $settlement->tarifa_hora)->toBe(1000.0)
        ->and((float) $settlement->total)->toBe(5000.0)
        ->and($settlement->status)->toBe('confirmada')
        ->and($settlement->timeEntries()->count())->toBe(2);

    expect(TimeEntry::query()->where('user_id', $employee->id)->whereNull('time_entry_settlement_id')->count())
        ->toBe(0);
});

test('despues de liquidar el reporte de pendientes vuelve a cero', function () {
    $employee = empleadoConCiclos();

    $this->actingAs(User::factory()->admin()->create(['activo' => true]));

    $antes = Livewire::test(TimeEntriesReport::class)->instance()->summaryRows();
    expect($antes->first()['pay'])->toBe(5000.0);

    TimeEntrySettlement::liquidar($employee, now()->toDateString());

    $despues = Livewire::test(TimeEntriesReport::class)->instance()->summaryRows();
    expect($despues)->toBeEmpty();
});

test('cambiar la tarifa del usuario no altera una liquidacion ya hecha', function () {
    $employee = empleadoConCiclos(1000);

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());

    $employee->update(['hourly_rate' => 9999]);

    $detalle = $settlement->fresh()->timeEntries()->with('user')->get();

    expect((float) $settlement->fresh()->total)->toBe(5000.0)
        ->and(round($detalle->sum(fn (TimeEntry $entry): float => $entry->pay()), 2))->toBe(5000.0);
});

test('los ciclos abiertos y los posteriores al corte no entran en la liquidacion', function () {
    $employee = empleadoConCiclos();

    // Abierto: nunca se liquida.
    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->subHours(4),
        'ended_at' => null,
    ]);

    // Cerrado pero posterior a la fecha de corte.
    TimeEntry::factory()->for($employee)->create([
        'started_at' => now()->addDays(3),
        'ended_at' => now()->addDays(3)->addHours(4),
    ]);

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());

    expect((float) $settlement->horas)->toBe(5.0)
        ->and($settlement->timeEntries()->count())->toBe(2);
});

test('liquidar dos veces seguidas no vuelve a tomar los ciclos ya liquidados', function () {
    $employee = empleadoConCiclos();

    TimeEntrySettlement::liquidar($employee, now()->toDateString());

    expect(fn () => TimeEntrySettlement::liquidar($employee, now()->toDateString()))
        ->toThrow(RuntimeException::class);

    expect(TimeEntrySettlement::query()->count())->toBe(1);
});

test('no se puede liquidar sin horas pendientes ni sin tarifa cargada', function () {
    $sinCiclos = User::factory()->administrativo()->hourlyRate(1000)->create();

    expect(fn () => TimeEntrySettlement::liquidar($sinCiclos))->toThrow(RuntimeException::class);

    $sinTarifa = User::factory()->administrativo()->create(['hourly_rate' => null]);
    TimeEntry::factory()->for($sinTarifa)->closed()->create();

    expect(fn () => TimeEntrySettlement::liquidar($sinTarifa))->toThrow(RuntimeException::class);
});

test('liquidar genera el gasto en finanzas con la categoria honorarios', function () {
    $employee = empleadoConCiclos();

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString(), [
        'medio_pago' => 'transferencia',
        'fecha_pago' => now()->toDateString(),
    ]);

    $expense = $settlement->expense;

    expect($expense)->not->toBeNull()
        ->and((float) $expense->monto)->toBe(5000.0)
        ->and($expense->medio_pago)->toBe('transferencia')
        ->and($expense->user_id)->toBe($employee->id)
        ->and($expense->comprobante_numero)->toBe($settlement->numero())
        ->and($expense->category->nombre)->toBe(TimeEntrySettlement::CATEGORIA_GASTO);
});

test('generar el gasto es idempotente', function () {
    $employee = empleadoConCiclos();

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());
    $settlement->generarGasto();

    expect(Expense::query()->count())->toBe(1);
});

test('anular devuelve los ciclos a pendiente y da de baja el gasto', function () {
    $employee = empleadoConCiclos();

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());
    $expenseId = $settlement->expense_id;

    $settlement->anular();

    expect($settlement->fresh()->status)->toBe('anulada')
        ->and($settlement->fresh()->expense_id)->toBeNull()
        ->and(Expense::query()->whereKey($expenseId)->exists())->toBeFalse();

    $pendientes = TimeEntry::query()
        ->where('user_id', $employee->id)
        ->whereNull('time_entry_settlement_id')
        ->get();

    expect($pendientes)->toHaveCount(2)
        ->and($pendientes->every(fn (TimeEntry $entry): bool => $entry->tarifa_hora === null))->toBeTrue();
});

test('anular dos veces no rompe nada', function () {
    $employee = empleadoConCiclos();

    $settlement = TimeEntrySettlement::liquidar($employee, now()->toDateString());

    $settlement->anular();
    $settlement->anular();

    expect($settlement->fresh()->status)->toBe('anulada')
        ->and(TimeEntry::query()->where('user_id', $employee->id)->whereNull('time_entry_settlement_id')->count())->toBe(2);
});

test('despues de anular los ciclos se pueden volver a liquidar', function () {
    $employee = empleadoConCiclos();

    TimeEntrySettlement::liquidar($employee, now()->toDateString())->anular();

    $nueva = TimeEntrySettlement::liquidar($employee, now()->toDateString());

    expect((float) $nueva->total)->toBe(5000.0)
        ->and($nueva->timeEntries()->count())->toBe(2);
});

test('previsualizar informa lo que se liquidaria sin tocar nada', function () {
    $employee = empleadoConCiclos();

    $resumen = TimeEntrySettlement::previsualizar($employee->id, now()->toDateString());

    expect($resumen['horas'])->toBe(5.0)
        ->and($resumen['tarifa'])->toBe(1000.0)
        ->and($resumen['total'])->toBe(5000.0)
        ->and($resumen['ciclos'])->toBe(2)
        ->and(TimeEntrySettlement::query()->count())->toBe(0);
});
