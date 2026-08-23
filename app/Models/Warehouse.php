<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nombre',
        'tipo',
        'predeterminado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
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
        static::saved(function (Warehouse $warehouse) {
            if ($warehouse->predeterminado) {
                static::where('id', '!=', $warehouse->id)->update(['predeterminado' => false]);
            }
        });
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
