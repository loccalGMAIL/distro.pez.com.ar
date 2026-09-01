<?php

namespace App\Models;

use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $time_entry_settlement_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property string|null $tarifa_hora
 */
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'time_entry_settlement_id',
        'started_at',
        'ended_at',
        'tarifa_hora',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'tarifa_hora' => 'decimal:2',
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
     * @return BelongsTo<TimeEntrySettlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(TimeEntrySettlement::class, 'time_entry_settlement_id');
    }

    /**
     * Un ciclo ya liquidado no vuelve a contar como pendiente de cobro.
     */
    public function isSettled(): bool
    {
        return $this->time_entry_settlement_id !== null;
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Ciclos todavía no incluidos en ninguna liquidación.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSinLiquidar(Builder $query): Builder
    {
        return $query->whereNull('time_entry_settlement_id');
    }

    /**
     * Ciclo actualmente abierto del usuario, si lo hay.
     */
    public static function openFor(User $user): ?self
    {
        return static::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();
    }

    /**
     * Inicia un ciclo para el usuario. Si ya tiene uno abierto, lo devuelve
     * en vez de crear otro (evita duplicar el fichaje con doble click).
     */
    public static function clockIn(User $user): self
    {
        return DB::transaction(function () use ($user): self {
            $open = static::query()
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            return $open ?? static::create([
                'user_id' => $user->id,
                'started_at' => now(),
            ]);
        });
    }

    /**
     * Horas entre started_at y ended_at (o el momento actual si sigue
     * abierto). No se persiste: se recalcula siempre a partir de esos dos
     * timestamps.
     */
    public function hours(): float
    {
        return round($this->started_at->diffInHours($this->ended_at ?? now(), true), 2);
    }

    /**
     * hours() * tarifa horaria (0 si no hay ninguna cargada). No se persiste,
     * sigue la misma lógica de "calcular, no denormalizar" usada en
     * PriceList::precioPara().
     *
     * Un ciclo ya liquidado usa la `tarifa_hora` que se le congeló al pagarlo:
     * cambiar `users.hourly_rate` después no puede reescribir un pago hecho.
     */
    public function pay(): float
    {
        return round($this->hours() * $this->tarifaAplicable(), 2);
    }

    /**
     * Tarifa con la que se valoriza este ciclo: la congelada si ya se liquidó,
     * la vigente del usuario si sigue pendiente.
     */
    public function tarifaAplicable(): float
    {
        return (float) ($this->tarifa_hora ?? $this->user->hourly_rate ?? 0);
    }

    /**
     * Ciclos cerrados para el informe mensual, opcionalmente filtrados por
     * empleado, por rango de fechas sobre started_at y por estado de
     * liquidación (`pendientes` / `liquidados`; null = todos). Es la query que
     * comparten el reporte en pantalla, el PDF y la liquidación, para que los
     * tres tomen exactamente los mismos ciclos.
     *
     * @return Builder<static>
     */
    public static function closedQuery(?int $userId = null, ?string $desde = null, ?string $hasta = null, ?string $liquidacion = null): Builder
    {
        return static::query()
            ->whereNotNull('ended_at')
            ->when($userId, fn (Builder $query, int $userId): Builder => $query->where('user_id', $userId))
            ->when($desde, fn (Builder $query, string $desde): Builder => $query->whereDate('started_at', '>=', $desde))
            ->when($hasta, fn (Builder $query, string $hasta): Builder => $query->whereDate('started_at', '<=', $hasta))
            ->when($liquidacion === 'pendientes', fn (Builder $query): Builder => $query->sinLiquidar())
            ->when($liquidacion === 'liquidados', fn (Builder $query): Builder => $query->whereNotNull('time_entry_settlement_id'));
    }

    /**
     * Agrupa ciclos por empleado, sumando horas y monto a cobrar.
     *
     * @param  Collection<int, self>  $entries
     * @return Collection<int, array{user: User, hours: float, pay: float}>
     */
    public static function summarize(Collection $entries): Collection
    {
        return $entries
            ->groupBy('user_id')
            ->map(function (Collection $group): array {
                $first = $group->first();
                assert($first instanceof self);

                return [
                    'user' => $first->user,
                    'hours' => round($group->sum(fn (self $entry): float => $entry->hours()), 2),
                    'pay' => round($group->sum(fn (self $entry): float => $entry->pay()), 2),
                ];
            })
            ->values();
    }
}
