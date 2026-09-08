<?php

namespace App\Models;

use Database\Factories\PerceptionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerceptionType extends Model
{
    /** @use HasFactory<PerceptionTypeFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nombre',
        'activo',
        'afecta_costo',
        'porcentaje',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'afecta_costo' => 'boolean',
            'porcentaje' => 'decimal:3',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return HasMany<PurchasePerception, $this>
     */
    public function purchasePerceptions(): HasMany
    {
        return $this->hasMany(PurchasePerception::class);
    }
}
