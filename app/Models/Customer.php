<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'razon_social',
        'cuit',
        'telefono',
        'email',
        'domicilio',
        'localidad',
        'condicion_pago',
        'balance',
        'price_list_id',
        'predeterminado',
        'observaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'predeterminado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Customer $customer) {
            if ($customer->predeterminado) {
                static::where('id', '!=', $customer->id)->update(['predeterminado' => false]);
            }
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'party');
    }
}
