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
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 */
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public function isOpen(): bool
    {
        return $this->ended_at === null;
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
     * hours() * tarifa horaria del usuario (0 si no tiene tarifa cargada).
     * No se persiste, sigue la misma lógica de "calcular, no denormalizar"
     * usada en PriceList::precioPara().
     */
    public function pay(): float
    {
        return round($this->hours() * (float) ($this->user->hourly_rate ?? 0), 2);
    }

    /**
     * Ciclos cerrados para el informe mensual, opcionalmente filtrados por
     * empleado y por rango de fechas sobre started_at.
     *
     * @return Builder<static>
     */
    public static function closedQuery(?int $userId = null, ?string $desde = null, ?string $hasta = null): Builder
    {
        return static::query()
            ->whereNotNull('ended_at')
            ->when($userId, fn (Builder $query, int $userId): Builder => $query->where('user_id', $userId))
            ->when($desde, fn (Builder $query, string $desde): Builder => $query->whereDate('started_at', '>=', $desde))
            ->when($hasta, fn (Builder $query, string $hasta): Builder => $query->whereDate('started_at', '<=', $hasta));
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
