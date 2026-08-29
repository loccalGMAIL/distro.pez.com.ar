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
     * saldo y el balance del proveedor. Es el único punto que cubre todos
     * los caminos de escritura (el RelationManager de Pagos es genérico y
     * no tiene hooks propios, y Purchase::anular() también borra
     * allocations en su loop de reversión).
     */
    protected static function booted(): void
    {
        static::saved(fn (PaymentAllocation $allocation) => $allocation->syncPurchaseBalance());
        static::deleted(fn (PaymentAllocation $allocation) => $allocation->syncPurchaseBalance());
    }

    private function syncPurchaseBalance(): void
    {
        if (! $this->allocatable instanceof Purchase) {
            return;
        }

        $this->allocatable->recalcularSaldo();
        $this->allocatable->supplier->recalcularBalance();
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
