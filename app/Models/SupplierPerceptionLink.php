<?php

namespace App\Models;

use Database\Factories\SupplierPerceptionLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupplierPerceptionLink extends Model
{
    /** @use HasFactory<SupplierPerceptionLinkFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'supplier_id',
        'description_key',
        'perception_type_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<PerceptionType, $this>
     */
    public function perceptionType(): BelongsTo
    {
        return $this->belongsTo(PerceptionType::class);
    }
}
