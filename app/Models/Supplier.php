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
     * todas sus compras, neta de lo que le quede sin imputar de sus pagos)
     * en vez de incrementar/decrementar a mano. No hace falta filtrar
     * compras por status: una compra en `borrador`/`anulada` ya tiene
     * `saldo = 0` (ver `Purchase::recalcularSaldo()`). Solo se restan los
     * pagos de `egreso` (lo que le pagamos al proveedor) — un `ingreso`
     * sería una devolución del proveedor hacia nosotros, no un anticipo
     * nuestro, así que no corresponde netearlo acá.
     */
    public function recalcularBalance(): void
    {
        $deuda = (float) $this->purchases()->sum('saldo');
        $aFavor = (float) $this->payments()->where('direccion', 'egreso')->sum('sin_imputar');
        $balance = $deuda - $aFavor;

        if ((float) $this->balance !== $balance) {
            $this->update(['balance' => $balance]);
        }
    }
}
