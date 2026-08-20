<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'cantidad',
        'precio_unit',
        'descuento',
        'subtotal',
        'costo_unit',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'precio_unit' => 'decimal:2',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'costo_unit' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
