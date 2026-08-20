<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'price_list_id',
        'warehouse_id',
        'user_id',
        'numero',
        'fecha',
        'subtotal',
        'descuento',
        'total',
        'saldo',
        'condicion_pago',
        'medio_pago',
        'status',
        'comprobante_path',
        'enviado_at',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'saldo' => 'decimal:2',
            'enviado_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if (! $sale->numero) {
                $next = 1 + (static::withTrashed()
                    ->whereNotNull('numero')
                    ->pluck('numero')
                    ->map(fn (string $numero): int => (int) preg_replace('/\D/', '', $numero))
                    ->max() ?? 0);

                $sale->numero = str_pad((string) $next, 8, '0', STR_PAD_LEFT);
            }

            // Sin envío por WhatsApp/comprobante todavía: se toma como
            // "enviada" el mismo momento en que se genera la venta.
            if (! $sale->enviado_at) {
                $sale->enviado_at = now();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    /**
     * Descuenta stock por cada línea (un stock_movement tipo "venta" por
     * línea). Idempotente: si ya se generaron movimientos para esta venta,
     * no vuelve a descontar.
     */
    public function deducirStock(): void
    {
        if ($this->stockMovements()->where('type', 'venta')->exists()) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->lines as $line) {
                $this->stockMovements()->create([
                    'product_id' => $line->product_id,
                    'warehouse_id' => $this->warehouse_id,
                    'quantity' => -$line->cantidad,
                    'unit_cost' => $line->costo_unit,
                    'type' => 'venta',
                    'user_id' => $this->user_id,
                    'motivo' => "Venta {$this->numero}",
                ]);
            }
        });
    }

    /**
     * Confirma la venta (si no lo estaba ya) y descuenta stock.
     */
    public function confirmar(): void
    {
        if ($this->status !== 'confirmada') {
            $this->status = 'confirmada';
            $this->save();
        }

        $this->deducirStock();
    }

    /**
     * Anula la venta: revierte el stock descontado (si lo había) generando
     * movimientos inversos tipo "devolucion" -sin borrar el historial-, y
     * revierte cualquier cobro imputado a esta venta para que la caja quede
     * consistente. Idempotente.
     */
    public function anular(): void
    {
        if ($this->status === 'anulada') {
            return;
        }

        DB::transaction(function () {
            $this->stockMovements()
                ->where('type', 'venta')
                ->get()
                ->each(function (StockMovement $movement) {
                    $this->stockMovements()->create([
                        'product_id' => $movement->product_id,
                        'warehouse_id' => $movement->warehouse_id,
                        'quantity' => -$movement->quantity,
                        'unit_cost' => $movement->unit_cost,
                        'type' => 'devolucion',
                        'user_id' => $this->user_id,
                        'motivo' => "Anulación venta {$this->numero}",
                    ]);
                });

            $this->paymentAllocations->each(function (PaymentAllocation $allocation) {
                $allocation->payment->increment('sin_imputar', $allocation->monto);
                $allocation->delete();
            });

            $this->status = 'anulada';
            $this->save();
        });
    }
}
