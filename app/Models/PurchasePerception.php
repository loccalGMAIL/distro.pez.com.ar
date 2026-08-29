<?php

namespace App\Models;

use Database\Factories\PurchasePerceptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchasePerception extends Model
{
    /** @use HasFactory<PurchasePerceptionFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'purchase_id',
        'perception_type_id',
        'descripcion',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * @return BelongsTo<PerceptionType, $this>
     */
    public function perceptionType(): BelongsTo
    {
        return $this->belongsTo(PerceptionType::class);
    }
}
