<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProductLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'description_key',
        'product_id',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Normaliza el texto de una línea de factura a una clave de búsqueda
     * estable: minúsculas, sin acentos, espacios colapsados y recortados.
     */
    public static function normalizeDescription(string $description): string
    {
        $folded = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $description) ?: $description;

        return trim(preg_replace('/\s+/', ' ', mb_strtolower($folded)));
    }
}
