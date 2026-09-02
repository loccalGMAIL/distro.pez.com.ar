<?php

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'user_id',
        'tipo_comprobante',
        'numero',
        'fecha',
        'vence_at',
        'subtotal',
        'descuento',
        'percepciones',
        'total',
        'saldo',
        'status',
        'archivo_path',
        'ocr_data',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'vence_at' => 'date',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'percepciones' => 'decimal:2',
            'total' => 'decimal:2',
            'saldo' => 'decimal:2',
            'ocr_data' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PurchaseLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    /**
     * @return HasMany<PurchasePerception, $this>
     */
    public function perceptions(): HasMany
    {
        return $this->hasMany(PurchasePerception::class);
    }

    /**
     * @return MorphMany<PaymentAllocation, $this>
     */
    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    /**
     * @return MorphMany<StockMovement, $this>
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    /**
     * Ingresa stock por cada línea (un stock_movement tipo "compra" por
     * línea) y actualiza el costo vigente del producto con el costo_unit
     * pagado. Idempotente: si ya se generaron movimientos para esta compra,
     * no vuelve a ingresar stock.
     */
    public function aumentarStock(): void
    {
        if ($this->stockMovements()->where('type', 'compra')->exists()) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->lines as $line) {
                $this->stockMovements()->create([
                    'product_id' => $line->product_id,
                    'warehouse_id' => $this->warehouse_id,
                    'quantity' => $line->cantidad,
                    'unit_cost' => $line->costo_unit,
                    'type' => 'compra',
                    'user_id' => $this->user_id,
                    'motivo' => "Compra {$this->numero}",
                ]);

                $line->product->update(['costo_ultimo' => $line->costo_unit]);
            }
        });
    }

    /**
     * Confirma la compra (si no lo estaba ya), ingresa stock y genera la
     * deuda con el proveedor.
     */
    public function confirmar(): void
    {
        DB::transaction(function () {
            if ($this->status !== 'confirmada') {
                $this->status = 'confirmada';
                $this->save();
            }

            $this->aumentarStock();
            $this->recalcularSaldo();
            $this->supplier->recalcularBalance();
        });
    }

    /**
     * Recalcula `saldo` desde la fuente de verdad (total − pagos imputados)
     * en vez de incrementar/decrementar a mano, para no desincronizarse.
     * Una compra en `borrador` o `anulada` no genera deuda: saldo = 0.
     * Sin `max(0, ...)`: un sobrepago queda como saldo negativo a propósito,
     * no se oculta.
     */
    public function recalcularSaldo(): void
    {
        $saldo = $this->status === 'confirmada'
            ? (float) $this->total - (float) $this->paymentAllocations()->sum('monto')
            : 0.0;

        if ((float) $this->saldo !== $saldo) {
            $this->update(['saldo' => $saldo]);
        }
    }

    /**
     * Anula la compra: revierte el stock ingresado (si lo había) generando
     * movimientos inversos tipo "devolucion_prov" -sin borrar el historial,
     * ni el costo_ultimo ya actualizado en los productos-, y revierte
     * cualquier pago imputado a esta compra para que la cuenta del
     * proveedor quede consistente. Idempotente.
     *
     * También limpia `numero`: es único por (supplier_id, tipo_comprobante,
     * numero), así que si se deja el número de una compra anulada, cargar de
     * nuevo el mismo comprobante (por error de carga, por ejemplo) choca con
     * esa fila muerta. `archivo_path` se deja intacto a propósito -sigue
     * siendo la evidencia del comprobante físico para auditar después-, solo
     * se limpia el campo que participa del unique.
     */
    public function anular(): void
    {
        if ($this->status === 'anulada') {
            return;
        }

        DB::transaction(function () {
            $this->stockMovements()
                ->where('type', 'compra')
                ->get()
                ->each(function (StockMovement $movement) {
                    $this->stockMovements()->create([
                        'product_id' => $movement->product_id,
                        'warehouse_id' => $movement->warehouse_id,
                        'quantity' => -$movement->quantity,
                        'unit_cost' => $movement->unit_cost,
                        'type' => 'devolucion_prov',
                        'user_id' => $this->user_id,
                        'motivo' => "Anulación compra {$this->numero}",
                    ]);
                });

            $this->paymentAllocations->each(function (PaymentAllocation $allocation) {
                $allocation->delete();
            });

            $this->status = 'anulada';
            $this->numero = null;
            $this->save();

            $this->recalcularSaldo();
            $this->supplier->recalcularBalance();
        });
    }
}
