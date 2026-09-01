<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Database\Factories\TimeEntrySettlementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Liquidación de honorarios de fichaje: asienta el pago de un período y saca
 * esos ciclos de "pendiente de cobro".
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $periodo_desde
 * @property Carbon $periodo_hasta
 * @property string $horas
 * @property string $tarifa_hora
 * @property string $total
 * @property string $status
 * @property Carbon $fecha_pago
 * @property string $medio_pago
 * @property string|null $referencia
 * @property string|null $observaciones
 * @property int|null $liquidated_by_user_id
 * @property int|null $expense_id
 *
 * @phpstan-type PendienteResumen array{horas: float, tarifa: float, total: float, ciclos: int, desde: ?string}
 */
class TimeEntrySettlement extends Model
{
    /** @use HasFactory<TimeEntrySettlementFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Categoría de gasto bajo la que se imputan las liquidaciones en Finanzas.
     */
    public const CATEGORIA_GASTO = 'Honorarios';

    protected $fillable = [
        'user_id',
        'periodo_desde',
        'periodo_hasta',
        'horas',
        'tarifa_hora',
        'total',
        'status',
        'fecha_pago',
        'medio_pago',
        'referencia',
        'observaciones',
        'liquidated_by_user_id',
        'expense_id',
    ];

    protected function casts(): array
    {
        return [
            'periodo_desde' => 'date',
            'periodo_hasta' => 'date',
            'fecha_pago' => 'date',
            'horas' => 'decimal:2',
            'tarifa_hora' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function liquidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liquidated_by_user_id');
    }

    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Ciclos cerrados del empleado todavía sin liquidar, hasta la fecha de
     * corte inclusive.
     *
     * @return Builder<TimeEntry>
     */
    public static function pendientes(int $userId, ?string $hasta = null): Builder
    {
        return TimeEntry::closedQuery($userId, null, $hasta, 'pendientes');
    }

    /**
     * Resumen de lo que se liquidaría, para mostrarlo en el formulario antes
     * de confirmar. Devuelve un array plano (no Collection) porque la forma
     * cruza métodos y Collection es invariante en TValue.
     *
     * @return PendienteResumen
     */
    public static function previsualizar(int $userId, ?string $hasta = null): array
    {
        /** @var Collection<int, TimeEntry> $entries */
        $entries = static::pendientes($userId, $hasta)->with('user')->orderBy('started_at')->get();

        $tarifa = (float) (User::query()->whereKey($userId)->value('hourly_rate') ?? 0);
        $horas = round($entries->sum(fn (TimeEntry $entry): float => $entry->hours()), 2);

        $primero = $entries->first();

        return [
            'horas' => $horas,
            'tarifa' => $tarifa,
            'total' => round($horas * $tarifa, 2),
            'ciclos' => $entries->count(),
            'desde' => $primero?->started_at->toDateString(),
        ];
    }

    /**
     * Liquida todo lo pendiente del empleado hasta la fecha de corte: congela
     * horas, tarifa y total, marca los ciclos incluidos y genera el gasto en
     * Finanzas.
     *
     * Todo bajo una transacción con lockForUpdate sobre los ciclos, mismo
     * recaudo que TimeEntry::clockIn() contra el doble click: dos pedidos
     * simultáneos no pueden liquidar los mismos fichajes dos veces.
     *
     * @param  array{fecha_pago?: string|null, medio_pago?: string|null, referencia?: string|null, observaciones?: string|null, liquidated_by_user_id?: int|null}  $datos
     *
     * @throws RuntimeException si el empleado no tiene tarifa o no hay horas pendientes
     */
    public static function liquidar(User $empleado, ?string $hasta = null, array $datos = []): self
    {
        $tarifa = (float) ($empleado->hourly_rate ?? 0);

        if ($tarifa <= 0) {
            throw new RuntimeException("El empleado {$empleado->name} no tiene tarifa horaria cargada.");
        }

        return DB::transaction(function () use ($empleado, $hasta, $datos, $tarifa): self {
            /** @var Collection<int, TimeEntry> $entries */
            $entries = static::pendientes($empleado->id, $hasta)
                ->lockForUpdate()
                ->orderBy('started_at')
                ->get();

            if ($entries->isEmpty()) {
                throw new RuntimeException("{$empleado->name} no tiene fichajes pendientes de liquidar en ese período.");
            }

            $horas = round($entries->sum(fn (TimeEntry $entry): float => $entry->hours()), 2);

            $primero = $entries->first();
            $ultimo = $entries->last();

            $settlement = static::create([
                'user_id' => $empleado->id,
                'periodo_desde' => $primero->started_at->toDateString(),
                'periodo_hasta' => $hasta ?? $ultimo->started_at->toDateString(),
                'horas' => $horas,
                'tarifa_hora' => $tarifa,
                'total' => round($horas * $tarifa, 2),
                'status' => 'confirmada',
                'fecha_pago' => $datos['fecha_pago'] ?? now()->toDateString(),
                'medio_pago' => $datos['medio_pago'] ?? 'efectivo',
                'referencia' => $datos['referencia'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'liquidated_by_user_id' => $datos['liquidated_by_user_id'] ?? null,
            ]);

            TimeEntry::query()
                ->whereIn('id', $entries->pluck('id'))
                ->update([
                    'time_entry_settlement_id' => $settlement->id,
                    'tarifa_hora' => $tarifa,
                ]);

            $settlement->generarGasto();

            return $settlement;
        });
    }

    /**
     * Genera el egreso en Finanzas por el total liquidado. Idempotente: si ya
     * hay un gasto asociado, no crea otro.
     */
    public function generarGasto(): void
    {
        if ($this->expense_id !== null) {
            return;
        }

        $categoria = ExpenseCategory::firstOrCreate(
            ['nombre' => self::CATEGORIA_GASTO],
            ['activo' => true],
        );

        $expense = Expense::create([
            'expense_category_id' => $categoria->id,
            'user_id' => $this->user_id,
            'fecha' => $this->fecha_pago,
            'descripcion' => "Liquidación de honorarios {$this->user->name} ({$this->periodoLegible()})",
            'monto' => $this->total,
            'medio_pago' => $this->medio_pago,
            'comprobante_numero' => $this->numero(),
        ]);

        $this->update(['expense_id' => $expense->id]);
    }

    /**
     * Anula la liquidación: devuelve los ciclos a "pendiente" (limpiando la
     * tarifa congelada, que vuelve a ser la vigente del usuario) y da de baja
     * el gasto generado. Idempotente, igual que Purchase::anular().
     *
     * No borra la liquidación: queda como fila anulada para poder auditar que
     * el pago existió y se revirtió.
     */
    public function anular(): void
    {
        if ($this->status === 'anulada') {
            return;
        }

        DB::transaction(function (): void {
            $this->timeEntries()->update([
                'time_entry_settlement_id' => null,
                'tarifa_hora' => null,
            ]);

            $this->expense?->delete();

            $this->status = 'anulada';
            $this->expense_id = null;
            $this->save();
        });
    }

    /**
     * Número de comprobante de la liquidación, usado también como
     * `comprobante_numero` del gasto para poder cruzarlos a ojo.
     */
    public function numero(): string
    {
        return 'LIQ-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function periodoLegible(): string
    {
        return $this->periodo_desde->format('d/m/Y').' — '.$this->periodo_hasta->format('d/m/Y');
    }

    /**
     * Recibo de pago en PDF, con el detalle de los ciclos incluidos.
     */
    public function pdf(): PdfDocument
    {
        return Pdf::loadView('pdf.time-entries.settlement-receipt', [
            'settlement' => $this,
            'entries' => $this->timeEntries()->with('user')->orderBy('started_at')->get(),
            'company' => CompanySetting::query()->first(),
        ]);
    }
}
