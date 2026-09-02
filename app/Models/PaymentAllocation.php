<?php

namespace App\Models;

use Database\Factories\PaymentAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentAllocation extends Model
{
    /** @use HasFactory<PaymentAllocationFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payment_id',
        'allocatable_type',
        'allocatable_id',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * Imputar o desimputar un pago cambia cuánto debe el proveedor: cada
     * alta/edición/baja de una allocation sobre una Purchase recalcula su
     * saldo y el balance del proveedor, y en cualquier caso (Purchase o
     * Sale) recalcula el `sin_imputar` del pago. Es el único punto que
     * cubre todos los caminos de escritura (el RelationManager de Pagos es
     * genérico y no tiene hooks propios, y Purchase::anular()/Sale::anular()
     * también borran allocations en su loop de reversión).
     */
    protected static function booted(): void
    {
        static::saved(fn (PaymentAllocation $allocation) => $allocation->sync());
        static::deleted(fn (PaymentAllocation $allocation) => $allocation->sync());
    }

    /**
     * `sin_imputar` del pago se recalcula primero porque el balance del
     * proveedor (Supplier::recalcularBalance()) depende de él para netear
     * el saldo a favor de los pagos sin aplicar.
     *
     * `->fresh()` en vez de la propiedad de relación directa: esta misma
     * instancia de PaymentAllocation puede disparar `sync()` varias veces
     * (alta y después edición) y Eloquent cachea la relación una vez
     * cargada, así que si el Payment o el allocatable se modificaron por
     * otro lado (otra instancia en memoria del mismo registro) entre una
     * llamada y la siguiente, `$this->payment`/`$this->allocatable` sin
     * `fresh()` devolverían el valor cacheado viejo, no el que hay en la
     * base.
     */
    private function sync(): void
    {
        $this->payment?->fresh()?->recalcularSinImputar();

        $allocatable = $this->allocatable?->fresh();

        if (! $allocatable instanceof Purchase) {
            return;
        }

        $allocatable->recalcularSaldo();
        $allocatable->supplier->recalcularBalance();
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }
}
