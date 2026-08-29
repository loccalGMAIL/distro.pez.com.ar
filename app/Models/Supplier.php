<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'codigo',
        'razon_social',
        'cuit',
        'telefono',
        'email',
        'domicilio',
        'condicion_pago',
        'dias_pago',
        'balance',
        'observaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'party');
    }

    /**
     * Recalcula `balance` desde la fuente de verdad (suma de `saldo` de
     * todas sus compras) en vez de incrementar/decrementar a mano. No hace
     * falta filtrar por status: una compra en `borrador`/`anulada` ya tiene
     * `saldo = 0` (ver `Purchase::recalcularSaldo()`).
     */
    public function recalcularBalance(): void
    {
        $balance = (float) $this->purchases()->sum('saldo');

        if ((float) $this->balance !== $balance) {
            $this->update(['balance' => $balance]);
        }
    }
}
