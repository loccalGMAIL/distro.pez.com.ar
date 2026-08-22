<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PriceList extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nombre',
        'codigo',
        'based_on_price_list_id',
        'porcentaje',
        'predeterminada',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'decimal:2',
            'predeterminada' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::saved(function (PriceList $priceList) {
            if ($priceList->predeterminada) {
                static::where('id', '!=', $priceList->id)->update(['predeterminada' => false]);
            }
        });
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function basedOn(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'based_on_price_list_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(PriceList::class, 'based_on_price_list_id');
    }

    /**
     * Cuánto multiplica esta lista al costo de cualquier producto, resolviendo
     * la cadena de "basada en" (independiente del producto, así se puede
     * calcular una sola vez por lista en vez de una vez por producto×lista).
     */
    public function multiplicador(array $visited = []): float
    {
        if (in_array($this->id, $visited, true)) {
            return 1.0;
        }

        $visited[] = $this->id;

        $factor = 1 + ((float) $this->porcentaje / 100);

        if (! $this->based_on_price_list_id) {
            return $factor;
        }

        return $factor * ($this->basedOn?->multiplicador($visited) ?? 1.0);
    }

    /**
     * Precio de un producto en esta lista: costo_ultimo × multiplicador().
     */
    public function precioPara(Product $product): string
    {
        return number_format((float) $product->costo_ultimo * $this->multiplicador(), 2, '.', '');
    }

    /**
     * True si asignar $candidateBasedOnId como based_on_price_list_id de esta
     * lista formaría una referencia circular (directa o a través de la cadena).
     */
    public function wouldCreateCycle(?int $candidateBasedOnId): bool
    {
        $currentId = $candidateBasedOnId;
        $visited = [];

        while ($currentId !== null) {
            if ($currentId === $this->id || in_array($currentId, $visited, true)) {
                return true;
            }

            $visited[] = $currentId;
            $currentId = static::where('id', $currentId)->value('based_on_price_list_id');
        }

        return false;
    }
}
