<?php

namespace App\Models;

use App\Services\PaymentAllocator;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'party_type',
        'party_id',
        'direccion',
        'fecha',
        'monto',
        'medio_pago',
        'referencia',
        'sin_imputar',
        'user_id',
        'comprobante_path',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
            'sin_imputar' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * Al borrar el pago (soft o forzado, Laravel dispara `deleted` en los
     * dos casos) hay que desimputarlo a mano: el `cascadeOnDelete` de la FK
     * solo actúa en un force delete y de todos modos se saltea los hooks de
     * Eloquent, así que `PaymentAllocation::deleted` nunca correría y las
     * compras quedarían con el saldo descontado por un pago inexistente.
     * Borrar una por una en vez de un `delete()` masivo es lo que dispara
     * ese hook. Al restaurar, se vuelve a repartir.
     */
    protected static function booted(): void
    {
        static::deleted(function (Payment $payment) {
            $payment->allocations->each->delete();

            if ($payment->party instanceof Supplier) {
                $payment->party->recalcularBalance();
            }
        });

        static::restored(fn (Payment $payment) => app(PaymentAllocator::class)->allocate($payment));
    }

    /**
     * `sin_imputar` es derivado (monto − imputado), nunca se tipea a mano:
     * mismo criterio que Purchase::recalcularSaldo() /
     * Supplier::recalcularBalance() (recalcular desde la fuente de verdad
     * en vez de incrementar/decrementar). Sin `max(0, ...)` a propósito: no
     * debería poder imputarse más que el monto del pago (lo valida el
     * form/el allocator), pero si pasara, se ve en vez de ocultarse.
     */
    public function recalcularSinImputar(): void
    {
        $sinImputar = (float) $this->monto - (float) $this->allocations()->sum('monto');

        if ((float) $this->sin_imputar !== $sinImputar) {
            $this->update(['sin_imputar' => $sinImputar]);
        }
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Razón social de la contraparte del pago (cliente o proveedor).
     */
    public function partyRazonSocial(): ?string
    {
        $party = $this->party;

        return $party instanceof Customer || $party instanceof Supplier
            ? $party->razon_social
            : null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
