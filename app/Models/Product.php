<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'nombre',
        'product_category_id',
        'base_unit',
        'costo_ultimo',
        'min_stock',
        'tracks_lot',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'costo_ultimo' => 'decimal:4',
            'min_stock' => 'decimal:3',
            'tracks_lot' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * @return HasMany<PurchaseLine, $this>
     */
    public function purchaseLines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    /**
     * @return HasMany<SaleLine, $this>
     */
    public function saleLines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Precio de venta para una lista de precios dada (ver PriceList::precioPara).
     * Sin lista, cae en costo_ultimo sin recargo (último recurso).
     */
    public function precioParaLista(?int $priceListId): string
    {
        $priceList = $priceListId !== null ? PriceList::find($priceListId) : null;

        if (! $priceList) {
            return number_format((float) $this->costo_ultimo, 2, '.', '');
        }

        return $priceList->precioPara($this);
    }

    /**
     * @return array<string, string>
     */
    public static function baseUnitOptions(): array
    {
        return [
            'unidad' => 'Unidad',
            'kg' => 'Kilogramo',
            'gramo' => 'Gramo',
            'litro' => 'Litro',
            'mililitro' => 'Mililitro',
            'docena' => 'Docena',
            'caja' => 'Caja',
            'bulto' => 'Bulto',
            'paquete' => 'Paquete',
            'metro' => 'Metro',
        ];
    }

    public function baseUnitLabel(): string
    {
        return self::baseUnitOptions()[$this->base_unit] ?? $this->base_unit;
    }
}
