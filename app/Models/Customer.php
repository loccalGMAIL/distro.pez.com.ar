<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (blank($customer->codigo)) {
                $customer->codigo = static::nextCodigo();
            }
        });

        static::saved(function (Customer $customer) {
            if ($customer->predeterminado) {
                static::where('id', '!=', $customer->id)->update(['predeterminado' => false]);
            }
        });
    }

    /**
     * Código correlativo: CLI-000001, CLI-000002, ... a partir del mayor
     * número ya usado (incluye clientes con soft delete, por el unique).
     */
    public static function nextCodigo(): string
    {
        $next = 1 + (static::withTrashed()
            ->where('codigo', 'like', 'CLI-%')
            ->pluck('codigo')
            ->map(fn (string $codigo): int => (int) Str::after($codigo, 'CLI-'))
            ->max() ?? 0);

        return 'CLI-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return BelongsTo<PriceList, $this>
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'party');
    }
}
