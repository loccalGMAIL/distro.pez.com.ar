<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

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
        'iva',
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
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
            'saldo' => 'decimal:2',
            'ocr_data' => 'array',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseLine::class);
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }
}
